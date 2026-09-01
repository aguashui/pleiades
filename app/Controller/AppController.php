<?php
class AppController extends Controller{
    public $components = array(
            'DebugKit.Toolbar',
            'Auth'=> array(
                    'authenticate' => array(
                            'Phpbb3' => array(
                                    'fields' => array('username' => 'username', 'password' => 'user_password'),
                                    'userModel' => 'User'
                            )
                    )
            ),
            'Session'
    );

    public $helpers = array(
            'Js',
            'Html'
    );

    public function beforeFilter() {
        if($this->request->header('User-Agent') == 'Bitfighter') {
            $this->layout = 'client';
        }

        $this->set('currentUserId', $this->Auth->user('user_id'));
        $this->set('currentUserName', $this->Auth->user('username'));
        $this->set('isAdmin', $this->isAdmin());

        if($this->Auth->user('user_id')) {
            $Notification = ClassRegistry::init('Notification');
            $this->set('notificationCount', $Notification->find('count', array('conditions' => array('user_id' => $this->Auth->user('user_id')))));
        }
    }

    public function isAdmin() {
        return (bool)($this->Auth->user('user_id') && $this->Session->read('isAdmin'));
    }
}
