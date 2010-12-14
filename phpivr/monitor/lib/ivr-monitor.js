/*
 *  ivr-monitor.js
 *
 *  Григорий Майстренко (Grygorii Maistrenko)
 *  grygoriim@gmail.com
 */

window.addEvent('domready', function() {
	
	var addCalls = function(calls) {
		//alert(calls);
		var table = new Element('table', {'class': 'table-calls'});
		var tr = new Element('tr', {'class': 'th-calls'}).inject(table);
		Element('td', {'class': 'th-calls', 'style': 'width:150px', 'html': 'Channel'}).inject(tr);
		Element('td', {'class': 'td-calls', 'style': 'width:300px', 'html': 'CallerID'}).inject(tr);
		Element('td', {'class': 'th-calls', 'style': 'width:150px', 'html': 'Location'}).inject(tr);
		Element('td', {'class': 'td-calls', 'style': 'width:100px', 'html': 'State'}).inject(tr);
		Element('td', {'class': 'td-calls', 'style': 'width:150px', 'html': 'Application'}).inject(tr);
		tr.inject(table);
		if (calls) calls.each(function(call) {
			var tr = new Element('tr', {'class': 'tr-calls'});
			Element('text', {'html': call.channel}).inject(Element('td', {'class': 'td-calls'}).inject(tr));
			Element('text', {'html': call.callerid}).inject(Element('td', {'class': 'td-calls'}).inject(tr));
			Element('text', {'html': call.location}).inject(Element('td', {'class': 'td-calls'}).inject(tr));
			Element('text', {'html': call.state}).inject(Element('td', {'class': 'td-calls'}).inject(tr));
			Element('text', {'html': call.application}).inject(Element('td', {'class': 'td-calls'}).inject(tr));
			tr.inject(table);
		});
		$('div-calls').empty();
		table.inject($('div-calls'));
	};
	
	// print emty table
	addCalls(null);
	
	var req = new Request.JSON({
		url: 'ivr-monitor.php',
		onComplete: function(jsonobj) { 
			//alert(calls);
			if (jsonobj) { addCalls(jsonobj.calls); } else { addCalls(null); };
		}
	});
	
	var RenewDivCalls = function (){
		req.send();
	}
	
	RenewDivCalls.periodical(5000);

});
