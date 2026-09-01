<?php
class User extends AppModel{
    public $name = 'User';
    public $primaryKey = 'user_id';
    public $displayField = 'username';
    public $useDbConfig = 'forum';
    public $hasMany = array(
        'Level' => array(
            'fields' => array(
                'id',
                'author',
                'name',
                'game_type',
                'team_count',
                'screenshot_filename',
                'rating',
                'downloads'
                )
            )
        );

    /**
     * Return an array of the groups that this user belongs to
     */
    public function getGroups() {
        if(!$this->id) {
            return array();
        }

        $groups = $this->query('SELECT group_id FROM phpbb_user_group WHERE user_id = ?;', array(intval($this->id)));
        $result = array();
        foreach($groups as $group) {
            array_push($result, $group['phpbb_user_group']['group_id']);
        }
        return $result;
    }
}
?>
