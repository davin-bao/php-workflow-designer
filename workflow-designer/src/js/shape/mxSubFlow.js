function mxSubFlow(bounds, fill, stroke, strokewidth)
{
	mxShape.call(this);
	this.bounds = bounds;
	this.fill = fill;
	this.stroke = stroke;
	this.strokewidth = (strokewidth != null) ? strokewidth : 1;
};

/**
 * Extends mxShape.
 */
mxUtils.extend(mxSubFlow, mxShape);

/**
 * Function: paintVertexShape
 *
 * Redirects to redrawPath for subclasses to work.
 */
mxSubFlow.prototype.paintVertexShape = function(c, x, y, w, h)
{
	c.translate(x, y);
	c.begin();
	this.redrawPath(c, x, y, w, h);
	c.fillAndStroke();
};

/**
 * Function: redrawPath
 *
 * Draws the path for this shape.
 */
mxSubFlow.prototype.redrawPath = function(c, x, y, w, h)
{
	c.moveTo(h/4, 0);
	c.lineTo(w-h/4, 0);
	c.curveTo(w-h/4, 0, w-h/4, h/4, w, h/4);
	c.lineTo(w, 3*h/4);
	c.curveTo(w, 3*h/4, w-h/4, 3*h/4, w-h/4, h);
	c.lineTo(h/4, h);
	c.curveTo(h/4, h, h/4, 3*h/4, 0, 3*h/4);
	c.lineTo(0, h/4);
	c.curveTo(0, h/4, h/4, h/4, h/4, 0);
	c.close();
};

mxCellRenderer.registerShape('subflow', mxSubFlow);