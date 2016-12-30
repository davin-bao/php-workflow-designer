var mxActions = {
    wnd: null,
    editor: null,
    init: function (editor) {
        var self = this;
        self.editor = editor;
        self._addAction('save', function(){
            editor.save(window.SAVE_URL + '&filename=' + mxUtils.prompt('Enter filename', window.OPENED_FILE));
        });
        self._addAction('new', function(){
            var filename ='/styles/default.xml';

            var xml = mxUtils.load(filename).getXml();
            self.editor.readGraphModel(xml.documentElement);
            self.editor.filename = window.OPENED_FILE = 'workflow.xml';
            self.editor.fireEvent(new mxEventObject(mxEvent.OPEN, 'filename', window.OPENED_FILE));
        });
        self._addAction('open', function(){
            self._popupFlowList();
        });
        self._addAction('addFlowProperty', function(){
            var attrName = mxUtils.prompt('new property', '');
            if(attrName == null || attrName == '') return;
            editor.graph.model.root.setAttribute(attrName, '');
            editor.refreshTasks();
        });
        self._addAction('removeFlowProperty', function(event){
            console.log(event);
            var attrName = mxUtils.prompt('new property', '');
            if(attrName == null || attrName == '') return;
            editor.graph.model.root.setAttribute(attrName, '');
        });

    }, open: function(src) {
        if(this.wnd != null){
            this.wnd.destroy();
        }
        window.OPENED_FILE = src;
        this.editor.open(window.OPEN_URL + '&filename=' + src);
    }, _popupFlowList: function() {
        var self = this;
        //load flow list
        mxUtils.get(window.INDEX_URL, function(req){
                if(req.getStatus() !== 200){
                    return mxUtils.error(window.INDEX_URL + ' ' + req.request.statusText, 200, false);
                }
                var activitiesDoc = req.getXml().documentElement;
                var content = '<table style="width: 100%;"><tr style="font-weight: bold;"><td>Label</td><td>Source</td><td>Operation</td></tr>';
                for(var i=0; i<activitiesDoc.children.length; i++){
                    var child = activitiesDoc.children[i];
                    if(child == null || child.nodeType != 1){
                        continue;
                    }
                    content += '<tr><td>' + child.getAttribute("label") + '</td><td>' + child.getAttribute("src") + '</td><td><button onclick="mxActions.open(\'' + child.getAttribute("src") + '\')" style="cursor: hand;">' + (mxResources.get('open') || 'Open') + '</button></td></tr>';
                }
                content += '</table>';
                self._popup(content);
            },
            function(){
                mxUtils.error(url + ' ' + 'load activity template error');
            });

        var table = document.createElement('table');

    }, _popup: function (content) {
        var div = document.createElement('div');
        div.style.overflow = 'scroll';
        div.style.width = '636px';
        div.style.height = '460px';
        var pre = document.createElement('pre');
        pre.innerHTML = content;//.replace(/\n/g,'<br>').replace(/ /g, '&nbsp;');

        div.appendChild(pre);

        var w = document.body.clientWidth;
        var h = Math.max(document.body.clientHeight || 0, document.documentElement.clientHeight)
        this.wnd = new mxWindow('Open ...', div, w/2-320, h/2-240, 640, 480, false, true);

        this.wnd.setClosable(true);
        this.wnd.setVisible(true);
    }, _addAction: function(tag, callback) {
        this.editor.addAction(tag, function(){
            if(this.wnd != null){
                this.wnd.destroy();
            }
            callback();
        });
    }
};


