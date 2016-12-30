
function mxTaskShape(bounds, fill, stroke, strokewidth)
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
mxUtils.extend(mxTaskShape, mxShape);

/**
 * Function: isHtmlAllowed
 *
 * Returns true for non-rounded, non-rotated shapes with no glass gradient.
 */
mxTaskShape.prototype.isHtmlAllowed = function()
{
	var events = true;
	
	if (this.style != null)
	{
		events = mxUtils.getValue(this.style, mxConstants.STYLE_POINTER_EVENTS, '1') == '1';		
	}
	
	return !this.isRounded && !this.glass && this.rotation == 0 && (events ||
		(this.fill != null && this.fill != mxConstants.NONE));
};

/**
 * Function: paintBackground
 * 
 * Generic background painting implementation.
 */
mxTaskShape.prototype.paintBackground = function(c, x, y, w, h)
{
	var events = true;
	
	if (this.style != null)
	{
		events = mxUtils.getValue(this.style, mxConstants.STYLE_POINTER_EVENTS, '1') == '1';		
	}
	
	if (events || (this.fill != null && this.fill != mxConstants.NONE) ||
		(this.stroke != null && this.stroke != mxConstants.NONE))
	{
		if (!events && (this.fill == null || this.fill == mxConstants.NONE))
		{
			c.pointerEvents = false;
		}
		
		if (this.isRounded)
		{
			var f = mxUtils.getValue(this.style, mxConstants.STYLE_ARCSIZE,
				mxConstants.RECTANGLE_ROUNDING_FACTOR * 100) / 100;
			var r = Math.min(w * f, h * f);
			c.roundrect(x, y, w, h, r, r);
		}
		else
		{
			c.rect(x, y, w, h);
		}
			
		c.fillAndStroke();
	}
};

/**
 * Function: paintForeground
 * 
 * Generic background painting implementation.
 */
mxTaskShape.prototype.paintForeground = function(c, x, y, w, h)
{
	if (this.glass && !this.outline && this.fill != null && this.fill != mxConstants.NONE)
	{
		this.paintGlassEffect(c, x, y, w, h, this.getArcSize(w + this.strokewidth, h + this.strokewidth));
	}
};

mxCellRenderer.registerShape('task', mxTaskShape);