//
// This is the main app for the sams module
//
function qruqsp_sams_main() {
    //
    // The panel to list the message
    //
    this.menu = new M.panel('message', 'qruqsp_sams_main', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.sams.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search message',
            'noData':'No message found',
            },
        'messages':{'label':'Recent Messages', 'type':'simplegrid', 'num_cols':1,
            'cellClasses':['multiline'],
            'noData':'No message',
            'addTxt':'Send Message',
            'addFn':'M.qruqsp_sams_main.message.open(\'M.qruqsp_sams_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('qruqsp.sams.messageSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.qruqsp_sams_main.menu.liveSearchShow('search',null,M.gE(M.qruqsp_sams_main.menu.panelUID + '_' + s), rsp.messages);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
//    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
//        return 'M.qruqsp_sams_main.message.open(\'M.qruqsp_sams_main.menu.open();\',\'' + d.id + '\');';
//    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'messages' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.from_callsign + '->' + d.to_callsign + '</span>'
                    + '<span class="subtext">' + d.content + '</span>';
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'messages' ) {
            return 'M.qruqsp_sams_main.message.open(\'M.qruqsp_sams_main.menu.open();\',\'' + d.id + '\',M.qruqsp_sams_main.message.nplist);';
        }
    }
    this.menu.refresh = function() {
        M.api.getJSONCb('qruqsp.sams.messageList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_sams_main.menu;
            p.data.messages = rsp.messages;
            p.refreshSection('messages');
        });
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.sams.messageList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_sams_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('refresh', 'Refresh', 'M.qruqsp_sams_main.menu.refresh();');
    this.menu.addClose('Back');

    //
    // The panel to edit Message
    //
    this.message = new M.panel('Message', 'qruqsp_sams_main', 'message', 'mc', 'medium', 'sectioned', 'qruqsp.sams.main.message');
    this.message.data = null;
    this.message.message_id = 0;
    this.message.nplist = [];
    this.message.sections = {
        'general':{'label':'', 'fields':{
            'from_callsign':{'label':'From', 'type':'text'},
            'to_callsign':{'label':'To', 'type':'text'},
            'content':{'label':'Message Content', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Send Now', 'fn':'M.qruqsp_sams_main.message.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_sams_main.message.message_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_sams_main.message.remove();'},
            }},
        };
    this.message.fieldValue = function(s, i, d) { return this.data[i]; }
    this.message.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.sams.messageHistory', 'args':{'tnid':M.curTenantID, 'message_id':this.message_id, 'field':i}};
    }
    this.message.open = function(cb, mid, list) {
        if( mid != null ) { this.message_id = mid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.sams.messageGet', {'tnid':M.curTenantID, 'message_id':this.message_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_sams_main.message;
            p.data = rsp.message;
            p.refresh();
            p.show(cb);
        });
    }
    this.message.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_sams_main.message.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.message_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.sams.messageUpdate', {'tnid':M.curTenantID, 'message_id':this.message_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.sams.messageAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_sams_main.message.message_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.message.remove = function() {
        if( confirm('Are you sure you want to remove message?') ) {
            M.api.getJSONCb('qruqsp.sams.messageDelete', {'tnid':M.curTenantID, 'message_id':this.message_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_sams_main.message.close();
            });
        }
    }
    this.message.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.message_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_sams_main.message.save(\'M.qruqsp_sams_main.message.open(null,' + this.nplist[this.nplist.indexOf('' + this.message_id) + 1] + ');\');';
        }
        return null;
    }
    this.message.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.message_id) > 0 ) {
            return 'M.qruqsp_sams_main.message.save(\'M.qruqsp_sams_main.message.open(null,' + this.nplist[this.nplist.indexOf('' + this.message_id) - 1] + ');\');';
        }
        return null;
    }
    this.message.addButton('save', 'Save', 'M.qruqsp_sams_main.message.save();');
    this.message.addClose('Cancel');
    this.message.addButton('next', 'Next');
    this.message.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'qruqsp_sams_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
