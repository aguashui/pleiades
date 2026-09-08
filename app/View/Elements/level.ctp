<?php
$tagList = '';
foreach($level['Tag'] as $k => $tag) {
  $link = $this->Html->link($tag['name'], '/levels/search/tags[0]:' . $tag['id'] , array('escape' => true));
  $tagList .= $this->Html->tag('li', $link, array('class' => 'tag'));
}

$author = empty($level['Level']['author']) ? $level['User']['username'] : $level['Level']['author'];

echo
    '<div class="level col-6 col-sm-6 col-lg-3">'
  . '<div class="level_header">'
  . $this->Html->link($level['Level']['name'], array('action' => 'view', $level['Level']['id']), array('class' => 'name', 'title' => $level['Level']['name']))
  . '<span class="rating">' . intval($level['Level']['rating']) . '</span>'
  . '<span class="author">by&nbsp;' . $this->Html->link($author, "/users/view/{$level['User']['user_id']}") . '</span>'
  . '<span class="level_info">'
  . '<span class="team_count">' . intval($level['Level']['team_count']) . '&nbsp;Team&nbsp;</span>'
  . '<span class="game_type">' . h($level['Level']['game_type']) . '</span>'
  . '</span>'
  . '</div>'
  . $this->Html->link($this->Html->image('t' . $level['Level']['screenshot_filename'], array('class' => 'thumbnail')), '/levels/view/' . $level['Level']['id'], array('class' => 'thumbnail-wrapper', 'escape' => false))
  . $this->Html->tag('ul', $tagList, array('class' => 'small-tags'))
  . '<div class="level-stats">'
  . '<div class="level-stats-comments">' . intval($level['Level']['comment_count']) . $this->Html->image('comments.png') . '</div>'
  . '<div class="level-stats-downloads">' . intval($level['Level']['downloads']) . $this->Html->image('downloads.png') . '</div>'
  . '</div>'
  . '</div>'
;
