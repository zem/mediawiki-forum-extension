<?php

function wfSpecialWriteArticle() {
    global $wgOut;
    global $wgRequest;
    global $wgUser; 
    $forum=new ForumClass();

    $dbr =& wfGetDB( DB_SLAVE );
    $dbw =& wfGetDB( DB_MASTER );

    $text="";
    $topic="";
    $reply_user=""; 
    
    $action=$wgRequest->getVal('action');
    $delete=$wgRequest->getInt('delete', 0);
    $edit=$wgRequest->getInt('edit', 0);
    $reply=$wgRequest->getInt('reply', 0);
    $thread=$wgRequest->getInt('thread', 0);
    $preview=$wgRequest->getVal('preview');





    ###########################################################
    # Delete query 
    ###########################################################
    if ( $delete ) {
	$wgOut->addWikiText("=".wfMsg("forum_delete_question", $delete)."=");
	
	$wgOut->addHTML('<a href="/index.php/Special:WriteArticle?action=delete&amp;id='.$delete.'">'.wfMsg("yes").'</a>&nbsp;&nbsp;<a href="/index.php/Special:Forum">'.wfMsg("no").'</a>');

	return;
    } 
    
    
    #################################################################################
    # process actions
    #################################################################################
    # preview the article first
    if ( $preview ) {
	$uid=$wgUser->getID(); 
	$topic=$wgRequest->getVal('topic');
	$text=$wgRequest->getVal('text');
	$wgOut->addHTML($forum->renderArticle($wgUser->getName(), "", $topic, $forum->mywordwrap($text)));
	$wgOut->addWikiText("----");
    } elseif ( $action == "post" ) {
	$uid=$wgUser->getID(); 
	$topic=$wgRequest->getVal('topic');
	$text=$wgRequest->getVal('text');
	
	if ( ! $topic ) {
		$wgOut->addWikiText('=Fehlender Betreff=');
		return; 
	}
	if ( ! $text ) {
		$wgOut->addWikiText('=Fehlender Text=');
		return; 
	}

	if ( ! $thread ) {
		$res=$dbw->query('
			INSERT INTO forum_thread 
				(user, topic, relevance) 
			VALUES 
				(
					'.$uid.", 
					".$dbw->addQuotes($topic).", 
					CURRENT_TIMESTAMP()
				)");

		$res=$dbw->query("SELECT id FROM forum_thread WHERE topic=".$dbw->addQuotes($topic)." ORDER BY relevance DESC");
		$row=$dbw->fetchObject($res);

		$thread=$row->id; 
	}

	$res=$dbw->query('INSERT INTO forum_article (user, date, thread, topic, text) VALUES ('.$uid.", CURRENT_TIMESTAMP(), $thread, ".$dbw->addQuotes($topic).", ".$dbw->addQuotes($forum->mywordwrap($text)).")");
	$row=$dbw->fetchObject($dbw->query('SELECT count(*) as article_count from forum_article WHERE thread='.$thread));	
	$res=$dbw->query('UPDATE forum_thread SET relevance=CURRENT_TIMESTAMP(), article_count='.($row->article_count - 1).', user='.$uid.' WHERE id='.$thread);
	
	
	$wgOut->addWikiText('=Artikel gesendet=');
	$wgOut->addHTML('Klicken sie <a href="/index.php/Special:Thread?thread='.$thread.'">hier</a> um zur&uuml;ck zum Thread zu gelangen.');
	return;
	
    } elseif ( $action=="update" ) {
	$uid=$wgUser->getID(); 
	$topic=$wgRequest->getVal('topic');
	$text=$wgRequest->getVal('text');
	$id=$wgRequest->getInt('id');
	if ( ! $id ) { 
		$wgOut->addWikiText('=Fehlende Id=');
		return; 
	}
	if ( ! $topic ) {
		$wgOut->addWikiText('=Fehlender Betreff=');
		return; 
	}
	if ( ! $text ) {
		$wgOut->addWikiText('=Fehlender Text=');
		return; 
	}
	#################################
	# user rights check
	################################
    	$upd_info = $dbw->fetchObject( 
		$dbw->query( '
			SELECT user, thread  
			FROM forum_article 
			WHERE id='.$id)
	);
	if ( ! $uid ) {
		$wgOut->addWikiText('=Log dich erst ein!=');
		return; 
	}
	if ( $upd_info->user != $uid ) {
		if ( ! $wgUser->isSysop() ) {
			$wgOut->addWikiText('=Das darfst du nicht!=');
			return; 
		}
	}
	
	$res=$dbw->query('UPDATE forum_article SET topic='.$dbw->addQuotes($topic).', text='.$dbw->addQuotes($forum->mywordwrap($text)).' WHERE id='.$id);

	$wgOut->addWikiText('=Artikel gesendet=');
	$wgOut->addHTML('Klicken sie <a href="/index.php/Special:Thread?thread='.$thread.'">hier</a> um zur&uuml;ck zum Thread zu gelangen.');
	return;
    } elseif ( $action=="delete" ) {
	$uid=$wgUser->getID(); 
	$id=$wgRequest->getInt('id');
	if ( ! $id ) { 
		$wgOut->addWikiText('=Fehlende Id=');
		return; 
	}
	#######################################################################
	# user rights check, nicht jeder darf hier
	######################################################################
    	$del_info = $dbw->fetchObject( 
		$dbw->query( '
			SELECT user, thread  
			FROM forum_article 
			WHERE id='.$id)
	);
	if ( ! $uid ) {
		$wgOut->addWikiText('=Log dich erst ein!=');
		return; 
	}
	if ( $del_info->user != $uid ) {
		if ( ! $wgUser->isSysop() ) {
			$wgOut->addWikiText('=Das darfst du nicht!=');
			return; 
		}
	}
	
	$res=$dbw->query('DELETE from forum_article WHERE id='.$id);

	$wgOut->addWikiText('=Artikel entfernt=');
	$wgOut->addHTML('Klicken sie <a href="/index.php/Special:Thread?thread='.$del_info->thread.'">hier</a> um zur&uuml;ck zum Thread zu gelangen.');

	return;
    }


    if ( ! $action ) { $action="post"; }
    
    if ( $edit != 0 ) { 
    	$reply=$edit; 
	$action="update";
    }
    
    if ( $reply != 0 ) {
    	$reply_info = $dbr->fetchObject( 
		$dbr->query( '
			SELECT user_name, thread, topic, text 
			FROM forum_article 
			LEFT JOIN '.$dbr->tableName( 'user' ).' ON user_id=user
			WHERE id='.$reply)
	);
	$thread=$reply_info->thread; 
	$topic=$reply_info->topic; 
	$from=$reply_info->user_name; 
	if ( ! $from ) { $from="Gast"; }
	if ( ! $edit ) {
		$text="\n\n'''[[Benutzer:$from|$from]] schrieb:'''\n\n> ".preg_replace('/\n/', "\n> ", $reply_info->text); 
	} else {
		$text=$reply_info->text; 
	}
    } elseif ( $thread != 0 ) {
    	$thread_info = $dbr->fetchObject( 
		$dbr->query( 'SELECT topic FROM forum_thread WHERE id='.$thread)
	);
	if ( $topic=="" ) { $topic=$thread_info->topic; }
    }

  
   $wgOut->addHTML('
   <form method="POST" action="/index.php?title=Spezial:WriteArticle&amp;action='.$action.'">
    <table>
    <tr>
    	<td><b>'.wfMsg('from').':</b></td>
	<td>');
     $wgOut->addWikiText('[[Benutzer:'.$wgUser->getName().'|'.$wgUser->getName().']]');
     $wgOut->addHTML('</td>
    </tr>
    <tr>
    	<td><b>'.wfMsg('subject').':</b></td>
	<td><input type="text" name="topic" value="'.$topic.'" size="80" maxlength="255"/></td>
    </tr>
    <tr>
    	<td valign="top"><b>'.wfMsg('message').':</b></td>
	<td><textarea name="text" cols="80" rows="20" wrap="virtual">'.$text.'</textarea></td>
     </tr>
     <tr>
     	<td colspan="2" align="center">
		<input type="hidden" name="thread" value="'.$thread.'"/>
		<input type="hidden" name="id" value="'.$reply.'"/>
		<input type="submit" name="submit" value="'.wfMsg('submit').'"/>
		<input type="submit" name="preview" value="'.wfMsg('preview').'"/>
	</td>
	</tr>
     </table>
	</form>
    ');
    $wgOut->addWikiText("----");
    $wgOut->addHTML($forum->showThread($thread,3));
}
//or, you can define a bunch of new classes to organize the logic
?>
