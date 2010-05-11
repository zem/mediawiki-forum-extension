<?php

function wfSpecialThread() {
    global $wgOut;
    global $wgRequest;

    $thread=$wgRequest->getInt('thread', 0);

	$forum=new ForumClass();
	
   $wgOut->addHTML($forum->showThread($thread));
}
//or, you can define a bunch of new classes to organize the logic
?>
