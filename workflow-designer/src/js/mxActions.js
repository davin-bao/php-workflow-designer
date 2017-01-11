var mxActions = {
    wnd: null,
    editor: null,
    activities: [],
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
        self._addAction('moveUp', function(){
            if (editor.graph != null) {
                if (!editor.graph.isSelectionEmpty()) {
                    var model = editor.graph.getModel();
                    model.beginUpdate();
                    var cells = editor.graph.getSelectionCells();
                    editor.graph.moveCells(cells, 0, -1);
                    model.endUpdate();
                }
            }
        });
        self._addAction('moveDown', function(){
            if (editor.graph != null) {
                if (!editor.graph.isSelectionEmpty()) {
                    var model = editor.graph.getModel();
                    model.beginUpdate();
                    var cells = editor.graph.getSelectionCells();
                    editor.graph.moveCells(cells, 0, 1);
                    model.endUpdate();
                }
            }
        });
        self._addAction('moveLeft', function(){
            if (editor.graph != null) {
                if (!editor.graph.isSelectionEmpty()) {
                    var model = editor.graph.getModel();
                    model.beginUpdate();
                    var cells = editor.graph.getSelectionCells();
                    editor.graph.moveCells(cells, -1, 0);
                    model.endUpdate();
                }
            }
        });
        self._addAction('moveRight', function(){
            if (editor.graph != null) {
                if (!editor.graph.isSelectionEmpty()) {
                    var model = editor.graph.getModel();
                    model.beginUpdate();
                    var cells = editor.graph.getSelectionCells();
                    editor.graph.moveCells(cells, 1, 0);
                    model.endUpdate();
                }
            }
        });
        self._addAction('help', function(){
            self.editor.helpWidth = 640;
            self.editor.helpHeight = 480;
            self.editor.showHelp();
        });
        self._addAction('loadActivity', function(){
            mxUtils.get(window.ACTIVITY_LIST_URL, function(req){
                    if(req.getStatus() !== 200){
                        return mxUtils.error(window.ACTIVITY_LIST_URL + ' ' + req.request.statusText, 200, true);
                    }
                    var activitiesDoc = req.getXml().documentElement;
                    for(var i=0; i<activitiesDoc.children.length; i++) {
                        var child = activitiesDoc.children[i];
                        if (child == null || child.nodeType != 1) {
                            continue;
                        }
                        var name = child.getAttribute("name");
                        var label = child.getAttribute("label");
                        var description = child.getAttribute("description");
                        var activity = [];
                        var key = name + '|' + label + '|' + description;
                        activity['name'] = name;
                        activity['label'] = label;
                        activity['description'] = description;
                        self.activities[key.toLowerCase()] = activity;
                    }
                },
                function(){
                    mxUtils.error(window.ACTIVITY_LIST_URL + ' ' + 'open activities error');
                });

            var req = mxUtils.load('/loadActivity.html');
            var root = req.getDocumentElement();
            self._popup('Search Activity', root.outerHTML);
            document.getElementById('match').focus();
        });

    }, searchActivity: function(val, event){
        var self = this, keyPressed = 0;
        //搜索活动
        var firstActivity = null, activities = [];
        for(var key in self.activities){
            if(key.indexOf(val.toLowerCase()) >= 0){
                activities[key] = self.activities[key];
                if(firstActivity == null) firstActivity = self.activities[key];
            }
        }
        //获取事件对象
        if(window.event) { // IE
            event = window.event;
            keyPressed = window.event.keyCode;
        } else {  // Firefox
            keyPressed = event.which;
        }

        //回车后，将插入第一个活动
        if(keyPressed==13 && val !== '' && firstActivity !== null && firstActivity.hasOwnProperty('name')) {
            var activityName = firstActivity['name'];
            activityName = activityName.substr(0, activityName.length - 8);
            if(!self.editor.templates.hasOwnProperty(activityName)){
                return;
            }

            self.wnd.destroy();
            //插入最上边的活动
            var cell = self.editor.templates[activityName];
            cell.vertex = true;
            //插入活动的坐标定义为当前绘图的中心
            var graphWidth = document.body.clientWidth;
            var graphHeight = Math.max(document.body.clientHeight || 0, document.documentElement.clientHeight);

            var parent = self.editor.graph.getDefaultParent();
            var cells = self.editor.graph.importCells([cell], graphWidth/2, graphHeight/2, parent);
            self.editor.graph.setSelectionCells(cells);

            return;
        }

        var tBodyDom = document.getElementById('activity-list').getElementsByTagName('tbody')[0];
        while(tBodyDom.hasChildNodes()){
            tBodyDom.removeChild(tBodyDom.firstChild);
        }
        var content = '', isFirstTr = true;;
        for(var key in activities){
            if(key.indexOf(val.toLowerCase()) >= 0){
                var tr = document.createElement('tr');
                if(isFirstTr){
                    isFirstTr = false;
                    tr.setAttribute('style', "background: #FFDDDD;opacity: 0.5;");
                }

                var nameTd = document.createElement('td');
                nameTd.setAttribute('align', "left");
                nameTd.setAttribute('valign', "middle");
                nameTd.innerHTML = self.activities[key]['name'];
                tr.appendChild(nameTd);

                var labelTd = document.createElement('td');
                labelTd.setAttribute('align', "left");
                labelTd.setAttribute('valign', "middle");
                labelTd.innerHTML = self.activities[key]['label'];
                tr.appendChild(labelTd);

                var descriptionTd = document.createElement('td');
                descriptionTd.setAttribute('align', "left");
                descriptionTd.setAttribute('valign', "middle");
                descriptionTd.innerHTML = self.activities[key]['description'];
                tr.appendChild(descriptionTd);

                var buttonTd = document.createElement('td');
                buttonTd.setAttribute('align', "center");
                buttonTd.setAttribute('valign', "middle");
                buttonTd.innerHTML = '<button onclick="" style="float: none;">' + (mxResources.get('open') || 'Open') + '</button>';
                tr.appendChild(buttonTd);

                tBodyDom.appendChild(tr);
            }
        }
        //tBodyDom.innerHTML = content;

    }, open: function(src) {
        var self = this;
        if(self.wnd != null){
            self.wnd.destroy();
        }
        window.OPENED_FILE = src;
        var filename = window.OPEN_URL + '&filename=' + src;
        if (filename != null)
        {
            mxUtils.get(filename, function(req){
                    if(req.getStatus() !== 200){
                        return mxUtils.error(filename + ' ' + req.request.statusText, 200, true);
                    }

                    var xml = req.getXml();
                    self.editor.readGraphModel(xml.documentElement);
                    self.editor.filename = filename;

                    self.editor.fireEvent(new mxEventObject(mxEvent.OPEN, 'filename', filename));
                },
                function(){
                    mxUtils.error(url + ' ' + 'open flow error');
                });
        }

        //this.editor.open(window.OPEN_URL + '&filename=' + src);
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
                self._popup('Open ...', content);
            },
            function(){
                mxUtils.error(url + ' ' + 'load activity template error');
            });

        var table = document.createElement('table');

    }, _popup: function (title, content) {
        var div = document.createElement('div');
        div.style.overflow = 'scroll';
        div.style.width = '636px';
        div.style.height = '460px';
        var pre = document.createElement('pre');
        pre.innerHTML = content;//.replace(/\n/g,'<br>').replace(/ /g, '&nbsp;');

        div.appendChild(pre);

        var w = document.body.clientWidth;
        var h = Math.max(document.body.clientHeight || 0, document.documentElement.clientHeight);
        this.wnd = new mxWindow(title, div, w/2-320, h/2-240, 640, 480, false, true);

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


