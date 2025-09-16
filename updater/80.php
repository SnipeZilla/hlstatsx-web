<?php
    if ( !defined('IN_UPDATER') )
    {
        die('Do not access this file directly.');
    }
    

    echo "Updating awards and actions for Counter-Strike 2<br />";

    // hlstats_Actions
    $db->query("
        UPDATE hlstats_Actions
        SET reward_player = 1 WHERE game='cs2' AND reward_player = 0 AND code = 'Begin_Bomb_Defuse_Without_Kit';
    ");
    $db->query("
        UPDATE hlstats_Actions
        SET reward_player = 2 WHERE game='cs2' AND reward_player = 0 AND code = 'Begin_Bomb_Defuse_With_Kit';
    ");

    $db->query("
        INSERT IGNORE INTO `hlstats_Awards` (`awardType`, `game`, `code`, `name`, `verb`) VALUES
            ('O', 'cs2', 'Begin_Bomb_Defuse_Without_Kit', 'Top Defuser', 'bomb defusing'),
            ('O', 'cs2', 'Begin_Bomb_Defuse_With_Kit', 'Top Defuser', 'bomb defusing with kit');
    ");

    echo "Done.<br />";

    $dbversion = 80;

    // Perform database schema update notification
    print "Updating database schema numbers.<br />";
    $db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");

?>
