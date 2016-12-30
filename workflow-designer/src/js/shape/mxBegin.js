function mxBegin(bounds, fill, stroke, strokewidth)
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
mxUtils.extend(mxBegin, mxShape);

/**
 * Function: paintVertexShape
 *
 * Redirects to redrawPath for subclasses to work.
 */
mxBegin.prototype.paintVertexShape = function(c, x, y, w, h)
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
mxBegin.prototype.redrawPath = function(c, x, y, w, h)
{
	var width = w/3;
	c.moveTo(0, h);
	c.curveTo(0, 3 * h / 5, 0, 2 * h / 5, w / 2, 2 * h / 5);
	c.curveTo(w / 2 - width, 2 * h / 5, w / 2 - width, 0, w / 2, 0);
	c.curveTo(w / 2 + width, 0, w / 2 + width, 2 * h / 5, w / 2, 2 * h / 5);
	c.curveTo(w, 2 * h / 5, w, 3 * h / 5, w, h);
	c.close();
};

mxCellRenderer.registerShape('begin', mxBegin);