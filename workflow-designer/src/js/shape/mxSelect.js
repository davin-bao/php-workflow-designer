function mxSelect(bounds, fill, stroke, strokewidth)
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
mxUtils.extend(mxSelect, mxShape);

/**
 * Function: paintVertexShape
 *
 * Redirects to redrawPath for subclasses to work.
 */
mxSelect.prototype.paintVertexShape = function(c, x, y, w, h)
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
mxSelect.prototype.redrawPath = function(c, x, y, w, h)
{
	c.moveTo(0, h/2);
	c.lineTo(w/2, 0);
	c.lineTo(w, h/2);
	c.lineTo(w/2, h);
	c.close();
};

mxCellRenderer.registerShape('select', mxSelect);