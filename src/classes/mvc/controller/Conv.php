<?php

namespace classes\mvc\controller;

use classes\entity\Message;
use classes\entity\User;
use classes\repository\Conversation;

class Conv extends Controller
{
    public function actionIndex($params = [])
    {
        $this->assert('login');

        $visitor = \Core::visitor();
        $conv_id = $params['PRIMARY_ID'];

        $conv_repo = $this->convRepo();
        $conv_list = $conv_repo->getConversations($visitor->user_id);

        $receiver = null;
        /** @var \classes\entity\Conversation $conv */
        $conv = null;
        if (isset($params['GET']['start']))
        {
            $receiver_id = $params['GET']['start'];
            $receiver = $this->app->find('User', $receiver_id);
            if ($receiver && $receiver->user_id && $receiver->user_id != $visitor->user_id)
            {
                $conv = $conv_repo->getConversation($visitor->user_id, $receiver->user_id);
                if (!$conv)
                {
                    $conv = $this->app->find('Conversation', [
                        'conversation_id' => $conv_repo->getLastConversationId() + 1,
                        'first_user_id' => $visitor->user_id,
                        'second_user_id' => $receiver->user_id,
                        'last_message_id' => 0
                    ]);
                    $conv->save();
                }
                $this->redirect('/conv' . $conv->conversation_id)->send();
            }
            else
            {
                $this->redirectToConv();
            }
        }
        else
        {
            $conv = $conv_list[$conv_id] ?? null;
            if ($conv && $conv->conversation_id)
            {
                $receiver = $conv->first_User->user_id == $visitor->user_id ? $conv->second_User : $conv->first_User;
            }
        }

        if ($conv_id && (!$receiver->user_id || !$conv || !$conv->conversation_id))
        {
            $this->redirectToConv();
        }
        $loaded = intval($params['POST']['loaded'] ?? 0);
        $message_list = $conv ? $conv_repo->getMessages($conv->conversation_id, 'desc', 20, $loaded) : [];

        if ($this->isAjax)
        {
            $messages = [];

            /** @var Message $message */
            foreach ($message_list as $id => $message)
            {
                $messages[$id] = $message->decode();
            }

            $this->ajax([
                'receiver' => $receiver->decode(),
                'conv' => $conv->decode(),
                'messages' => $messages,
                'last_message' => $_SESSION['lastMessage_' . $conv->conversation_id] ?? ''
            ]);
        }
        else
        {
            $last_messages = $conv_repo->getLastMessages($conv_list);

            return $this->view->content('conversation', [
                'sidebar' => false,
                'conv_id' => $conv_id,
                'conv' => $conv,
                'conv_list' => $conv_list,
                'receiver' => $receiver,
                'message_list' => $message_list,
                'conv_repo' => $conv_repo,
                'last_messages' => $last_messages,
                'title' => 'Сообщения'
            ]);
        }
    }

    public function actionSend($params = [])
    {
        $conv_id = $params['PRIMARY_ID'];
        if (!$this->isPost)
        {
            $this->redirect('/conv' . $conv_id)->send();
        }

        $this->assert('login');

        $visitor = \Core::visitor();
        if (!$conv_id)
        {
            return $this->actionIndex();
        }

        /** @var \classes\entity\Conversation $conv */
        $conv = $this->app->find('Conversation', $conv_id);
        if ($conv->first_User->user_id != $visitor->user_id && $conv->second_User->user_id != $visitor->user_id) {
            $this->redirectToConv();
        }

        $data = $params['POST'];

        $message = $data['message'] ?? '';
        $message = trim($message);
        if (!strlen($message))
        {
            $this->ajax([
                'status' => 'error',
                'notify' => [
                    'error' => 'Please enter the correct message!'
                ]
            ]);
        }
        $message = preg_replace('/^(<br\s?\/?>)+|(<br\s?\/?>)+$/', '', $message);
        $message = preg_replace('/(<br\s?\/?>){3,}/', '<br><br>', $message);
        $message = htmlspecialchars($message);
        $message = str_replace('&lt;br&gt;', '<br>', trim($message));
        $message = htmlspecialchars_decode($message);
        /** @var Message $message */
        $message = $this->app->find('Message', ['message_id' => $this->convRepo()->getLastMessageId() + 1, 'conversation_id' => $conv_id, 'date' => date('Y-m-d H:i:s', time()), 'message' => $message, 'sender_user_id' => $visitor->user_id, 'is_read' => false]);
        $message->save();

        /** @var User $receiver */
        $receiver = $conv->first_User->user_id == $visitor->user_id ? $conv->second_User : $conv->first_User;

        $conv->setLastMessageId($message->message_id);
        $conv->save();

        $root = $this->app->getRouter()->root;

        $_SESSION['lastMessage_' . $conv->conversation_id] = '';

        $this->reply(['target' => [$visitor->user_id, $receiver->user_id], 'data' => ['type' => 'new_message', 'conv_id' => $conv->conversation_id, 'message' => $message->decode()]]);
    }

    public function actionTyping($params = [])
    {
        $this->assert('login');

        $conv_id = $params['PRIMARY_ID'];
        if (!$this->isPost)
        {
            $this->redirectToConv($conv_id);
        }
        /** @var \classes\entity\Conversation $conv */
        $conv = $this->app->find('Conversation', $conv_id);
        if (!$conv->conversation_id)
        {
            $this->redirectToConv();
        }
        $visitor = \Core::visitor();
        if ($conv->first_user_id != $visitor->user_id && $conv->second_user_id != $visitor->user_id)
        {
            $this->redirectToConv();
        }

        $receiver_id = $conv->first_user_id == $visitor->user_id ? $conv->second_user_id : $conv->first_user_id;

        if (isset($params['POST']['message'])) {
            $_SESSION['lastMessage_' . $conv->conversation_id] = $params['POST']['message'];
        }

        $this->reply([
            'target' => $receiver_id,
            'data' => [
                'type' => 'typing_message',
                'typer' => $visitor->decode(),
                'conv_id' => $conv->conversation_id,
                'state' => $params['POST']['state'] ?? 'stop'
            ]
        ]);
    }

    public function actionRead($params = [])
    {
        $this->assert('login');

        $conv_id = $params['PRIMARY_ID'];
        if (!$this->isPost)
        {
            $this->redirectToConv($conv_id);
        }
        /** @var \classes\entity\Conversation $conv */
        $conv = $this->app->find('Conversation', $conv_id);
        if (!$conv->conversation_id)
        {
            $this->redirectToConv();
        }
        $visitor = \Core::visitor();
        if ($conv->first_user_id != $visitor->user_id && $conv->second_user_id != $visitor->user_id)
        {
            $this->redirectToConv($conv_id);
        }
        $data = $params['POST'];
        $messages = isset($data['m']) ? explode(',', $data['m']) : [];
        if (!$messages) {
            return;
        }
        if ($this->convRepo()->readMessages($visitor->user_id, $conv->conversation_id, $messages))
        {
            $receiver_id = $conv->first_user_id == $visitor->user_id ? $conv->second_user_id : $conv->first_user_id;

            $this->reply([
                'target' => [$visitor->user_id, $receiver_id],
                'data' => [
                    'type' => 'read',
                    'conv_id' => $conv->conversation_id,
                    'messages' => $messages
                ]
            ]);
        }
    }

    /** @return Conversation */
    protected function convRepo()
    {
        return $this->app->repository('Conversation');
    }

    protected function redirectToConv($conv_id = '')
    {
        $this->redirect('/conv' . $conv_id)->send();
    }
}