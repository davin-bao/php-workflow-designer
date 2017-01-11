function mxEnd(bounds, fill, stroke, strokewidth)
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
mxUtils.extend(mxEnd, mxShape);

/**
 * Function: paintVertexShape
 *
 * Redirects to redrawPath for subclasses to work.
 */
mxEnd.prototype.paintVertexShape = function(c, x, y, w, h)
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
mxEnd.prototype.redrawPath = function(c, x, y, w, h)
{
	c.moveTo(h/2, 0);
	c.lineTo(w-h/2, 0);
	c.curveTo(w-h/2, 0, w, 0, w, h/2);
	c.curveTo(w, h/2,w, h, w-h/2, h);
	c.lineTo(h/2, h);
	c.curveTo(h/2, h, 0, h, 0, h/2);
	c.curveTo(0, h/2, 0, 0, h/2, 0);
	c.close();
};

mxCellRenderer.registerShape('end', mxEnd);