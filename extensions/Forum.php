<?php

require_once("ForumClass.php");

$wgExtensionFunctions[] = "wfExtensionSpecialForum";

function wfExtensionSpecialForum() {
    global $wgMessageCache;
    require_once('includes/SpecialPage.php');
    $wgMessageCache->addMessages(array(
    	'forum' => 'Das Wagendorf Forum',
    	'thread' => 'Das Wagendorf Forum',
    	'writearticle' => 'Das Wagendorf Forum'
    )); //will expand

	//add forum Messages
    $wgMessageCache->addMessages(array(
    	'startthread' => 'neues Thema beginnen',
    	'thread_not_found' => 'Thema nicht gefunden',
    	'forum_delete_question' => 'Beitrag $1 wirklich l&ouml;schen?',
    	'yes' => 'Ja',
    	'no' => 'Nein',
    	'subject' => 'Betreff',
    	'message' => 'Nachricht',
    	'from' => 'Von',
    	'submit' => 'absenden',
    	'quote' => 'Zitat',
    	'guest' => 'Gast',
    	'answer' => 'antworten',
    	'answers' => 'Antworten'
    )); //will expand
    /* also used */
    // edit
    // preview
    // delete


    // the name 'example' above should NOT have any capital letter.
    SpecialPage::addPage( new SpecialPage( 'Forum' ) );
    SpecialPage::addPage( new SpecialPage( 'Thread', '', false ) );
    SpecialPage::addPage( new SpecialPage( 'WriteArticle', '', false ) );
}
//extension specific configuration options (like new user groups and perms) here


$wgExtensionFunctions[] = "wfShowForum";

function wfShowForum() {
    global $wgParser;
    # register the extension with the WikiText parser
    # the first parameter is the name of the new tag.
    # In this case it defines the tag <example> ... </example>
    # the second parameter is the callback function for
    # processing the text between the tags
    $wgParser->setHook( "showforum", "renderShowForum" );
}

$wgExtensionFunctions[] = "wfShowThread";

function wfShowThread() {
    global $wgParser;
    # register the extension with the WikiText parser
    # the first parameter is the name of the new tag.
    # In this case it defines the tag <example> ... </example>
    # the second parameter is the callback function for
    # processing the text between the tags
    $wgParser->setHook( "showthread", "renderShowThread" );
}

# The callback function for converting the input text to HTML output
function renderShowForum( $input ) {
    $forum=new ForumClass();
    return $forum->showForum(10);
}

function renderShowThread( $input ) {
    global $wgOut;
    global $wgTitle;
    global $wgRequest;
    global $wgUser;
    $dbr =& wfGetDB( DB_SLAVE );

    $thread=intval($input);
    $forum=new ForumClass();

    return $forum->showThread($thread);

}

?>
