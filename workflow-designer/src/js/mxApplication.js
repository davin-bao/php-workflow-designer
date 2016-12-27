/*
 * Copyright (c) 2006-2013, JGraph Ltd
 *
 * Defines the startup sequence of the application.
 */
{
	//加载自定义shape
	mxClient.include('js/shape/mxBegin.js');
	/**
	 * Constructs a new application (returns an mxEditor instance)
	 */
	function mxApplication(config)
	{
		var editor = null;
		
		var hideSplash = function()
		{
			// Fades-out the splash screen
			var splash = document.getElementById('splash');
			
			if (splash != null)
			{
				try
				{
					mxEvent.release(splash);
					mxEffects.fadeOut(splash, 100, true);
				}
				catch (e)
				{
					splash.parentNode.removeChild(splash);
				}
			}
		};
		
		try
		{
			if (!mxClient.isBrowserSupported())
			{
				mxUtils.error('Browser is not supported!', 200, false);
			}
			else
			{
				mxObjectCodec.allowEval = true;
				var node = mxUtils.load(config).getDocumentElement();
				editor = new mxEditor(node);
				mxObjectCodec.allowEval = false;
				
				// Adds active border for panning inside the container
				editor.graph.createPanningManager = function()
				{
					var pm = new mxPanningManager(this);
					pm.border = 30;
					
					return pm;
				};
				
				editor.graph.allowAutoPanning = true;
				editor.graph.timerAutoScroll = true;
				
				// Updates the window title after opening new files
				var title = document.title;
				var funct = function(sender)
				{
					document.title = title + ' - ' + sender.getTitle();
				};
				
				editor.addListener(mxEvent.OPEN, funct);
				
				// Prints the current root in the window title if the
				// current root of the graph changes (drilling).
				editor.addListener(mxEvent.ROOT, funct);
				funct(editor);
				
				// Displays version in statusbar
				editor.setStatus('mxGraph '+mxClient.VERSION);

				// Shows the application
				hideSplash();
			}
		}
		catch (e)
		{
			hideSplash();

			// Shows an error message if the editor cannot start
			mxUtils.alert('Cannot start application: ' + e.message);
			throw e; // for debugging
		}
		addTemplate(editor);
		addGraphStyle(editor);
		addToolbar(editor);

		return editor;
	}

	function addToolbar(editor){

		//工具条中添加按钮
		var icon = 'images/begin.gif';
		var pressedIcon = 'images/rounded.gif';
		var template = 'Begin';
		var toggle = true;
		var text = '';
		var style = 'begin';
		var as = 'Begin';

		var cell = editor.templates[template];

		if (cell != null && style != null)
		{
			cell = editor.graph.cloneCells([cell])[0];
			cell.setStyle(style);
		}

		var insertFunction = null;

		if (text != null && text.length > 0 && mxDefaultToolbarCodec.allowEval)
		{
			insertFunction = mxUtils.eval(text);
		}
		editor.toolbar.addPrototype(as, icon, cell, pressedIcon, insertFunction, toggle);
	}

	function addGraphStyle(editor){
		var style = new Object();
		style[mxConstants.STYLE_SHAPE] = 'begin';
		style[mxConstants.STYLE_FILLCOLOR] = '#FFDDEE';
		editor.graph.getStylesheet().putCellStyle('begin', style);
	}

	function addTemplate(editor){

		var xml = '<add as="Begin" icon="images/begin.gif" pressedIcon="images/rounded.gif" style="begin"><BeginActivity label="Begin" description="" href=""><mxCell vertex="1" style="begin"><mxGeometry as="geometry" width="32" height="32"/></mxCell></BeginActivity></add>';
		var doc = mxUtils.parseXml(xml).documentElement;

		var name = doc.getAttribute('as');
		var child = doc.firstChild;

		while (child != null && child.nodeType != 1)
		{
			child = child.nextSibling;
		}

		if (child != null)
		{
			var codec = new mxCodec(doc.documentElement);
			editor.addTemplate(name, codec.decodeCell(child));
		}
	}

}
