<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_sams_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['message'] = array(
        'name' => 'Message',
        'sync' => 'yes',
        'o_name' => 'message',
        'o_container' => 'messages',
        'table' => 'qruqsp_sams_messages',
        'fields' => array(
            'msg_id' => array('name'=>'ID'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'from_callsign' => array('name'=>'From Callsign'),
            'to_callsign' => array('name'=>'To Callsign'),
            'path' => array('name'=>'Digipeater Path', 'default'=>''),
            'content' => array('name'=>'Message Content'),
            'hops' => array('name'=>'Hops', 'default'=>'0'),
            ),
        'history_table' => 'qruqsp_sams_history',
        );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
