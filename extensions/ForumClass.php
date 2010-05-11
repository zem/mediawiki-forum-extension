<?php

/* 

Wiki Forum Class
Copyright (C) 2006 Hans Freitag

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/


class ForumClass { 

##########################################
# Helper Functions 
#
# some useful helper functions
#########################################
function mywordwrap($text) {
    global $wgUser; 

	#if ( $wgUser->getID() != 2 ) {	
	#	return $text; 
	#}

	$text_out=''; 
	$lines=spliti("\n", $text);
	
	foreach ($lines as $line) {
		# don't touch quoted lines
		if ( preg_match('/^\>/', $line) ) {
			$text_out.=$line."\n";
		} else {
			$text_out.=wordwrap($line."\n");
		}
	}

	return $text_out;
}

###########################################
# SQL functions
#
# every db query has it's own function
###########################################
# query the headline of a thread
function sqlThreadTopic($thread) {
    $dbr =& wfGetDB( DB_SLAVE );
    
    $res = $dbr->query( '
    	SELECT user_name, topic, relevance, article_count
	FROM forum_thread
	LEFT JOIN '.$dbr->tableName( 'user' ).' ON user_id=user 
	WHERE id='.$thread
    );
    $row = $dbr->fetchObject( $res ); 
    return $row;
}




#############################################
# render Functions
#
# render the content in small parts 
#############################################

# shows an article 
function renderArticle ($user_name, $date, $topic, $text) {
    	global $wgOut;

    	$user=$user_name;
	if ( ! $user ) { $user=wfMsg('guest'); }
	$output.=$wgOut->parse('[[User:'.$user.'|'.$user.']] '.$date);
	$output.=$wgOut->parse('');
	$output.=$wgOut->parse("'''".$topic."'''");
	$output.=$wgOut->parse('');
	$text=preg_replace('/\n/', "\n ", $text);
	$output.=$wgOut->parse(' '.$text);	
	$output.=$wgOut->parse('');

	return $output;
}

# schows the article answer/action Buttons
function renderArticleButtons ($id, $thread) {
    	global $wgOut;
    	global $wgUser;

    	$user=$user_name;

	## Hier kommen die Bearbeiten und Löschen Buttons mit user rights
    	$output.='<a href="/index.php?title=Special:WriteArticle&amp;thread='.$thread.'">'.wfMsg('answer').'</a> &nbsp;&nbsp;';
    	$output.='<a href="/index.php?title=Special:WriteArticle&amp;reply='.$id.'">'.wfMsg('quote').'</a> &nbsp;&nbsp;';

	if ( ! $wgUser->getID() ) {
		# nop
	} elseif ( $wgUser->getID() == $row->user ) {
    		$output.='<a href="/index.php?title=Special:WriteArticle&amp;edit='.$id.'">'.wfMsg('edit').'</a> &nbsp;&nbsp;';
    		$output.='<a href="/index.php?title=Special:WriteArticle&amp;delete='.$id.'">'.wfMsg('delete').'</a> &nbsp;&nbsp;';
	} elseif ( $wgUser->isSysop() ) {
    		$output.='<a href="/index.php?title=Special:WriteArticle&amp;edit='.$id.'">'.wfMsg('edit').' SysOP</a> &nbsp;&nbsp;';
    		$output.='<a href="/index.php?title=Special:WriteArticle&amp;delete='.$id.'">'.wfMsg('delete').' SysOP</a> &nbsp;&nbsp;';
	}

	$output.=$wgOut->parse('----');

	return $output;
}



function showThread( $thread=0, $last_articles=0 ) {
    global $wgOut;
    global $wgTitle;
    global $wgRequest;
    global $wgUser;
    $dbr =& wfGetDB( DB_SLAVE );

    $thread=intval($thread);

    if ( $thread==0 ) {
    	$output=wfMsg('thread_not_found');
	
	return $output; 
    }

    list( $limit, $offset ) = wfCheckLimits(10);
   
   if ( ! $last_articles ) {
    $sl = wfViewPrevNext( $offset, $limit , $wgTitle->getPrefixedDBKey(), "action=purge" );
    
    # Make Thread Topic
    $row = $this->sqlThreadTopic( $thread ); 

    $output.=$wgOut->parse('=='.$row->topic.'==');
    #$wgOut->addWikiText('----');

   $output.=wfMsg( 'answers' ).": ".$row->article_count."<br/>$sl<br/><hr/>";
   } else {
	$limit=$last_articles;
	$desc=" DESC ";
   }
   $res = $dbr->query( '
    	SELECT 
		id, 
		user,
		user_name, 
		date, 
		topic, 
		text 
	FROM forum_article
	LEFT JOIN '.$dbr->tableName( 'user' ).' ON user=user_id 
	WHERE thread='.$thread.'
	ORDER BY date ' .$desc. 
	$dbr->limitResult( $limit,$offset ));

    while ( $row = $dbr->fetchObject( $res ) ) {
	$output.=$this->renderArticle($row->user_name, $row->date, $row->topic, $row->text);
	$output.=$this->renderArticleButtons($row->id, $thread);
    }
   
   $output.="$sl";

   return $output;
}

# zeigt das Forum an 
function showForum($list_max=0) {
    global $wgOut;
    $dbr =& wfGetDB( DB_SLAVE );

    list( $limit, $offset ) = wfCheckLimits();

    if ( ! $list_max ) {
   	 $sl = wfViewPrevNext( $offset, $limit , 'Special:Forum' );

    	$output.='<a href="/index.php/Special:WriteArticle">Neues Thema beginnen</a><br/>
        <br/>'.$sl.'<br/><br/>';
	
    } else {
	$limit=$list_max;
	$offset=0;
    } 
    $res = $dbr->query( '
    		SELECT id, user_name, topic, relevance, article_count 
		FROM forum_thread
		LEFT JOIN '.$dbr->tableName( 'user' ).' ON user_id = user 
		ORDER BY relevance DESC' . $dbr->limitResult( $limit,$offset ));

    $output.='<table  border="1" cellpadding="5" cellspacing="0">
		<tr>
			<th style="background:#eeeeee;">Betreff</th>
			<th style="background:#eeeeee;">Antworten</th>
			<th style="background:#eeeeee;">Letzter Beitrag</th>
		</tr>';
     
    while ( $row = $dbr->fetchObject( $res ) ) {
        	$output.='<tr>
			<td><a href="/index.php?title=Special:Thread&amp;thread='.$row->id.'">'.$row->topic.'</a></td>
			<td>';

    	$user=$row->user_name;
	if ( ! $user ) { $user=wfMsg('guest'); }

	$output.=$row->article_count."</td><td>".$row->relevance;
	
	$output.=$wgOut->parse('[[User:'.$user.'|'.$user.']]');
	
	$output.='</td></tr>';
    }
    
    $output.=' </table>';

    if ( $sl ) {
	$output.='<br/>'.$sl.'<br/>';
    }
    
    return $output;
}


}

?>
