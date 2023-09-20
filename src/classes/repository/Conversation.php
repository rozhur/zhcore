<?php

namespace classes\repository;

class Conversation extends Repository
{
    public function getConversations($user_id, $receiver_id = 0, $limit = 0): array
    {
        $conv_find = $this->app->getDb()->query('select * from core_conversation where first_user_id = ' . $user_id . ($receiver_id ? ' and second_user_id = ' . $receiver_id : '') . ' or second_user_id = ' . $user_id . ($receiver_id ? ' and first_user_id = ' . $receiver_id : '') . ($limit ? ' limit ' . $limit : ''))->fetchAll();
        $conv_list = [];
        foreach ($conv_find as $i => $row)
        {
            /** @var \classes\entity\Conversation $conv */
            $conv = $this->app->find('Conversation', $row);
            $conv_list[$conv->conversation_id] = $conv;
        }
        return $conv_list;
    }

    /** @return \classes\entity\Conversation */
    public function getConversation($user_id, $receiver_id)
    {
        $result = $this->getConversations($user_id, $receiver_id, 1);
        return array_shift($result);
    }

    public function getMessages($conv_id, $sort = 'desc', $limit = 0, $loaded = 0)
    {
        $messages_find = $this->app->getDb()->query('select * from core_message where conversation_id = ' . $conv_id . ' order by message_id ' . $sort . ($limit ? ' limit ' . $limit . ($loaded ? ' offset ' . ($loaded) : '') : ''))->fetchAll();
        $message_list = [];
        foreach ($messages_find as $i => $row)
        {
            /** @var \classes\entity\Message $message */
            $message = $this->app->find('Message', $row);
            $message_list[$message->message_id] = $message;
        }
        usort($message_list, function ($a, $b) {
            return strcmp($a->date, $b->date);
        });
        return $message_list;
    }

    public function getLastConversationId(): int
    {
        $conv_find = $this->app->getDb()->query('select conversation_id from core_conversation order by conversation_id desc limit 1')->fetch();

        return intval($conv_find['conversation_id'] ?? 0);
    }

    public function getLastMessageId(): int
    {
        $conv_find = $this->app->getDb()->query('select message_id from core_message order by message_id desc limit 1')->fetch();

        return intval($conv_find['message_id'] ?? 0);
    }

    public function getUnreadMessagesCount($user_id = 0): array
    {
        if (!$user_id)
        {
            return [];
        }
        $conv_find = $this->app->getDb()->query('select conversation_id from core_conversation where first_user_id = ' . $user_id . ' or second_user_id = ' . $user_id)->fetchAllColumn();
        if (!count($conv_find))
        {
            return [];
        }
        $messages_find = $this->app->getDb()->query('select * from core_message where sender_user_id not like ' . $user_id . ' and is_read = 0 and conversation_id in (' . implode(',', $conv_find) . ')')->fetchAll();
        $unread_list = [];
        foreach ($messages_find as $key => $message)
        {
            $unread_list[$message['conversation_id']][$message['message_id']] = $this->app->find('Message', $message);
        }
        return $unread_list;
    }

    public function readMessages($author_id, $conv_id, $messages = []): int
    {
        if (!$conv_id || !count($messages))
        {
            return 0;
        }
        $this->app->getDb()->query('update core_message set is_read = 1 where sender_user_id not like ' . $author_id . ' and conversation_id = ' . $conv_id . ' and message_id in (' . implode(',', $messages) . ') and is_read = 0');

        return $this->app->getDb()->getConnection()->affected_rows;
    }

    public function getLastMessages($conversations = []): array
    {
        if (!count($conversations))
        {
            return [];
        }
        $ids = [];
        /** @var \classes\entity\Conversation $conv */
        foreach ($conversations as $id => $conv)
        {
            $ids[] = $conv->last_message_id;
        }
        if (!count($ids))
        {
            return [];
        }
        $messages_find = $this->app->getDb()->query('select * from core_message where message_id in (' . implode(',', $ids) . ') order by STR_TO_DATE(\'date\', \'%Y-%m-%d %H:%m\') desc')->fetchAll();

        $message_list = [];
        foreach ($messages_find as $i => $message)
        {
            $message_list[$message['conversation_id']] = $this->app->find('Message', $message);
        }
        usort($message_list, function ($a, $b) {
            return strcmp($b->date, $a->date);
        });
        return $message_list;
    }
}