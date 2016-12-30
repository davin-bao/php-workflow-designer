var mxActivityManager = {
	editor: null,
	init: function(){
		mxClient.include('js/shape/mxBegin.js');
	},
	loadTemplate: function (editor) {
		var self = this;
		self.editor = editor;
		var url = '/server.php?_action=templates';
		mxUtils.get(url, function(req){
			if(req.getStatus() !== 200){
				return mxUtils.error(url + ' ' + req.request.statusText, 200, false);
			}
			var activitiesDoc = req.getXml().documentElement;
			for(var i=0; i<activitiesDoc.children.length; i++){
				var child = activitiesDoc.children[i];
				if(child == null || child.nodeType != 1){
					continue;
				}
				self._addTemplate(child);
				self._addGraphStyle(child);
				self._addToolbar(child);
			}
		},
			function(){
			mxUtils.error(url + ' ' + 'load activity template error');
		});
	},
	_addToolbar: function (activityDoc){
		//工具条中添加按钮
		var icon = activityDoc.getAttribute('icon');
		var pressedIcon = activityDoc.getAttribute('pressedIcon');
		var template = activityDoc.getAttribute('as'), asName = template;
		var toggle = true;
		var text = '';
		var style = activityDoc.getAttribute('style');

		var cell = this.editor.templates[template];

		if (cell != null && style != null)
		{
			cell = this.editor.graph.cloneCells([cell])[0];
			cell.setStyle(style);
		}

		var insertFunction = null;

		if (text != null && text.length > 0 && mxDefaultToolbarCodec.allowEval)
		{
			insertFunction = mxUtils.eval(text);
		}
		this.editor.toolbar.addPrototype(asName, icon, cell, pressedIcon, insertFunction, toggle);
	},
	_addGraphStyle: function (activityDoc){
		var styleName = activityDoc.getAttribute('style');
		var style = new Object();
		style[mxConstants.STYLE_SHAPE] = styleName;
		style[mxConstants.STYLE_FILLCOLOR] = activityDoc.getAttribute('fillColor');
		this.editor.graph.getStylesheet().putCellStyle(styleName, style);
	},
	_addTemplate: function (activityDoc){
		var name = activityDoc.getAttribute('as');
		var child = activityDoc.firstChild;
		var code = new mxCodec(activityDoc.documentElement);

		while (child != null && child.nodeType != 1)
		{
			child = child.nextSibling;
		}

		if (child != null)
		{
			this.editor.addTemplate(name, code.decodeCell(child));
		}
	}, loadProperties: function (editor) {
		var self = this;
		self.editor = editor;
		editor.createProperties = function (cell)
		{
			var model = this.graph.getModel();
			var value = model.getValue(cell);

			if (mxUtils.isNode(value))
			{
				// Creates a form for the user object inside
				// the cell
				var form = new mxForm('properties');

				// Adds a readonly field for the cell id
				var id = form.addText('ID', cell.getId());
				id.setAttribute('readonly', 'true');

				var geo = null;
				var yField = null;
				var xField = null;
				var widthField = null;
				var heightField = null;

				// Adds fields for the location and size
				if (model.isVertex(cell))
				{
					geo = model.getGeometry(cell);

					if (geo != null)
					{
						yField = form.addText('top', geo.y);
						xField = form.addText('left', geo.x);
						widthField = form.addText('width', geo.width);
						heightField = form.addText('height', geo.height);
					}
				}

				// Adds a field for the cell style
				var tmp = model.getStyle(cell);
				var style = form.addText('Style', tmp || '');

				// Creates textareas for each attribute of the
				// user object within the cell
				var attrs = value.attributes;
				var texts = [];

				for (var i = 0; i < attrs.length; i++)
				{
					// Creates a textarea with more lines for
					// the cell label
					var name = attrs[i].nodeName;
					var value = attrs[i].value;
					var rows = (attrs[i].nodeName == 'label') ? 4 : 2;
					var input = document.createElement('textarea');
					if (mxClient.IS_NS)
					{
						rows--;
					}
					input.setAttribute('rows', rows || 2);
					input.value = value;
					var tr = document.createElement('tr');
					var td = document.createElement('td');
					mxUtils.write(td, name);
					tr.appendChild(td);
					if(editor.graph.isSelectionEmpty() && name != 'label' && name != 'description' && name != 'href'){
						var button = document.createElement('button');
						button.setAttribute('target', attrs[i].nodeName);
						button.setAttribute('style', 'float: right;');
						mxUtils.write(button, mxResources.get('del') || 'DEL');
						td.appendChild(button);

						mxEvent.addListener(button, 'click', function(event) {
							var attrName = event.currentTarget.getAttribute('target');
							cell.getValue().removeAttribute(attrName);
							editor.hideProperties();
							editor.showProperties(cell);
						});
					}

					td = document.createElement('td');
					td.appendChild(input);
					tr.appendChild(td);
					form.body.appendChild(tr);


					texts[i] =  input;

					//texts[i] = form.addTextarea(attrs[i].nodeName, val,
					//	(attrs[i].nodeName == 'label') ? 4 : 2);
				}

				// Adds an OK and Cancel button to the dialog
				// contents and implements the respective
				// actions below

				// Defines the function to be executed when the
				// OK button is pressed in the dialog
				var okFunction = mxUtils.bind(this, function()
				{
					// Hides the dialog
					this.hideProperties();

					// Supports undo for the changes on the underlying
					// XML structure / XML node attribute changes.
					model.beginUpdate();
					try
					{
						if (geo != null)
						{
							geo = geo.clone();

							geo.x = parseFloat(xField.value);
							geo.y = parseFloat(yField.value);
							geo.width = parseFloat(widthField.value);
							geo.height = parseFloat(heightField.value);

							model.setGeometry(cell, geo);
						}

						// Applies the style
						if (style.value.length > 0)
						{
							model.setStyle(cell, style.value);
						}
						else
						{
							model.setStyle(cell, null);
						}

						// Creates an undoable change for each
						// attribute and executes it using the
						// model, which will also make the change
						// part of the current transaction
						for (var i=0; i<attrs.length; i++)
						{
							var edit = new mxCellAttributeChange(
								cell, attrs[i].nodeName,
								texts[i].value);
							model.execute(edit);
						}

						// Checks if the graph wants cells to
						// be automatically sized and updates
						// the size as an undoable step if
						// the feature is enabled
						if (this.graph.isAutoSizeCell(cell))
						{
							this.graph.updateCellSize(cell);
						}
					}
					finally
					{
						model.endUpdate();
					}
				});

				// Defines the function to be executed when the
				// Cancel button is pressed in the dialog
				var cancelFunction = mxUtils.bind(this, function()
				{
					// Hides the dialog
					this.hideProperties();
				});

				//var addPropertyFunction = mxUtils.bind(this, function () {
                //
				//	var attrName = mxUtils.prompt('new property', '');
				//	if(attrName == null || attrName == '') return;
				//	// Hides the dialog
				//	this.hideProperties();
                //
				//	// Supports undo for the changes on the underlying
				//	// XML structure / XML node attribute changes.
				//	model.beginUpdate();
				//	try
				//	{
				//		cell.setAttribute(attrName, '');
				//	}
				//	finally
				//	{
				//		model.endUpdate();
				//	}
				//});

				form.addButtons(okFunction, cancelFunction);

				return form.table;
			}

			return null;
		};
	}
};

(function(){
	mxActivityManager.init();
})();

