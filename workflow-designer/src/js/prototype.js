//
//mxForm.prototype.addButtons = function(okFunct, cancelFunct, addPropertyFunct)
//{
//    var tr = document.createElement('tr');
//    var td = document.createElement('td');
//    tr.appendChild(td);
//    td = document.createElement('td');
//
//    // Adds the ok button
//    var button = document.createElement('button');
//    mxUtils.write(button, mxResources.get('ok') || 'OK');
//    td.appendChild(button);
//
//    mxEvent.addListener(button, 'click', function()
//    {
//        okFunct();
//    });
//
//    // Adds the addProperty button
//    button = document.createElement('button');
//    mxUtils.write(button, mxResources.get('addProperty') || 'Add Property');
//    td.appendChild(button);
//
//    mxEvent.addListener(button, 'click', function()
//    {
//        addPropertyFunct();
//    });
//
//    // Adds the cancel button
//    button = document.createElement('button');
//    mxUtils.write(button, mxResources.get('cancel') || 'Cancel');
//    td.appendChild(button);
//
//    mxEvent.addListener(button, 'click', function()
//    {
//        cancelFunct();
//    });
//
//    tr.appendChild(td);
//    this.body.appendChild(tr);
//};
//
