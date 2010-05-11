<?php
function wfSpecialForum() {
    global $wgOut;
    $dbr =& wfGetDB( DB_SLAVE );
    $forum=new ForumClass;
    $wgOut->addHTML($forum->showForum(0));
}
//or, you can define a bunch of new classes to organize the logic
?>
