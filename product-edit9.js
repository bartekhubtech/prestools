function checkPrices()
{ var rv = document.getElementsByClassName("price");
  for(var i in rv) { 
    if(rv[i].value.indexOf(',') != -1) { 
      alert("Please use dots instead of comma's for the prices!");
      rv.focus();
      return false;
    }
  }
  return true;
}

var rowsremoved = 0;
function RemoveRow(row)
{ var trow = document.getElementById("trid"+row).parentNode;
  trow.innerHTML = "<td></td>";
  rowsremoved++;
}

function cat_change(num)
{ var list = eval("Mainform.categorylist"+num);
  var fld = document.getElementById('category_number'+num);
  fld.value = list.options[list.selectedIndex].value;
}

function catsel_change(num)
{ var list = eval("Mainform.categorysel"+num);
  var fld = document.getElementById('category_number'+num);
  fld.value = list.options[list.selectedIndex].value;
}

/* the following section takes care of conditional inclusion of the TinyMCE file */
var TMCE_head = document.getElementsByTagName('head')[0];
var TMCE_js = document.createElement("script");
TMCE_js.type = "text/javascript";
var TMCE_loaded = false;

function TMCE_loadonce()
{ if(TMCE_loaded)
    return;
//  Prestashop settings can be found at /js/tinymce.inc.js
//  TMCE_js.src = "http://cdn.tinymce.com/4/tinymce.min.js";
  TMCE_js.src = "tinymce/tinymce.min.js";
  TMCE_head.appendChild(TMCE_js);
  TMCE_loaded = true;
}

function check_shopz(rowno)
{	var shopz_arr = document.getElementsByName("shopz"+rowno+"[]");
	if(shopz_arr.length > 0)          
	{ var found = false;
      for(var x=0; x<shopz_arr.length; x++)
		  if(shopz_arr[x].checked)
			  found=true;
	  if(!found)
	  { alert("At least one shop must be selected for a product!");
		return false;		  
	  }
	}
	return true;
}

function RowSubmit(elt)
{ var tmp, res;
  var subtbl = document.getElementById("subtable");
  var row = elt.parentNode.parentNode;
  var rowno = row.childNodes[0].id.substr(4);
  
  if(!check_discounts(rowno)) return false; /* check for duplicate key errors in discounts that would result in errors */
  if(!check_shopz(rowno)) return false; /* check that at least one shop is selected */
  var subrow = subtbl.appendChild(row.cloneNode(true));
  
  var selects = row.getElementsByTagName('select');
  for(let k=0;k<selects.length;k++)  
  { if(!selects[k].name) continue;
    document.rowform[selects[k].name].selectedIndex = selects[k].selectedIndex;
  }

  var areas = row.getElementsByTagName('textarea');
  for(let k=0;k<areas.length;k++)  
  { if(areas[k].name.substring(0,11) == "description") /* catches description_short too */
	{ if(areas[k].parentNode.childNodes[0].tagName == "DIV") 
      { //alert(tinyMCE.get(areas[k].name).getContent());
		tmp = tinyMCE.get(areas[k].name).getContent();
	    document.rowform[areas[k].name].value = tidy_html(tmp).trim();
	  }
	  else /* no TinyMCE: check for integrity */
	  { tmp = tidy_html(areas[k].value).trim();
	    document.rowform[areas[k].name].value = tmp;
	  }
	  if(areas[k].name.substring(0,17) == "description_short")
		  res = check_description("description_short", tmp, psdlimit, -1);
	  else
		  res = check_description("description", tmp, 21844, -1);
	  if(!res)
		throw new Error("Description field too long!"); 
	}
  } 
  
  var tmp = document.getElementById('featureblock0');
  if(tmp.style.display == 'none')
    rowform.featuresset.value = 0;
  else
    rowform.featuresset.value = 1;
  rowform.verbose.value = SwitchForm.verbose.checked;
  if(rowform.verbose.checked)
     rowform.target="_blank";
  else
    rowform.target="tank";
  rowform.skipindexation.value = IndexForm.skipindexation.checked;  
  rowform.allshops.value = Mainform.allshops.value;  
  rowform.submittedrow.value = rowno; 
  rowform.submit();
  subtbl.removeChild(subrow);
}

/* RowSubmit2 is no longer used and only stored for "spare parts" */
function RowSubmit2(elt)
{ var subtbl = document.getElementById("subtable");
  var row = elt.parentNode.parentNode;
  var rowno = row.childNodes[0].id.substr(4);
  if(!check_discounts(rowno)) return false; /* check for duplicate key errors in discounts that would result in errors */
  if(!check_shopz(rowno)) return false; /* check that at least one shop is selected */
  var find = "shopz"+rowno+"\\[\\]";
  var re = new RegExp(find, 'g');
  var tmp = row.innerHTML.replace(re, "shopz[]"); /* we need to rename shopz here as otherwise getElementsByName won't work because of duplicates */
  subtbl.innerHTML = '<tr>'+tmp+'</tr>';
  // field contents are not automatically copied
  var inputs = row.getElementsByTagName('input');
  var shopz_done = false;
  for(var k=0;k<inputs.length;k++)
  { if(((inputs[k].name.substring(0,6) == "active") || (inputs[k].name.substring(0,7) == "on_sale") || (inputs[k].name.substring(0,11) == "online_only") || (inputs[k].name.substring(0,9) == "dl_delete") || (inputs[k].name.substring(0,10) == "is_virtual")) && (inputs[k].type == "hidden"))
	{ elt = document.rowform[inputs[k].name][0]; /* the trick with the hidden field works not with the rowsubmit so we delete it */
	  elt.parentNode.removeChild(elt);
	  continue;
	}
	/* for shopz we copy and remove the number for several fields with the same name (like "shopz5[]") */
	else if(inputs[k].name.substring(0,5) == "shopz")
	{ if(shopz_done) continue;
	  shopz_done = true;
	  var tabfields = document.getElementsByName("shopz"+rowno+"[]");
	  var rowfields = document.getElementsByName("shopz[]");	  
	  for(var m=0; m<tabfields.length; m++)
	  { rowfields[m].checked = tabfields[m].checked;
		rowfields[m].value = tabfields[m].value;
	  }
	}
    else if(inputs[k].type != "button")
    { if(inputs[k].name == "") continue; /* skip category_number: the field between the category select tables */
	  if(((inputs[k].name.substring(0,6) == "active") || (inputs[k].name.substring(0,7) == "on_sale") 
		|| (inputs[k].name.substring(0,11) == "online_only") || (inputs[k].name.substring(0,10) == "is_virtual")
		|| (inputs[k].name.substring(0,9) == "dl_delete")) 
		&& (inputs[k].type == "checkbox"))
	  { document.rowform[inputs[k].name].type = "text";
	    if(!inputs[k].checked) document.rowform[inputs[k].name].value = "0"; /* value will initially always be "1" */
	  }
	  else	
	  {	document.rowform[inputs[k].name].value = inputs[k].value;
	  }
      var temp = document.rowform[inputs[k].name].name;
      temp = temp.replace(/[0-9]*$/, ""); /* chance "description1" into "description" */
      document.rowform[inputs[k].name].name = temp;
    }
  }
  var areas = row.getElementsByTagName('textarea');
  for(let k=0;k<areas.length;k++)  
  { if(((areas[k].name.substring(0,17) == "description_short") || (areas[k].name.substring(0,11) == "description")) && (areas[k].parentNode.childNodes[0].tagName == "DIV"))
    { //alert(tinyMCE.get(areas[k].name).getContent());
	  document.rowform[areas[k].name].value = tinyMCE.get(areas[k].name).getContent();
	}
	else if((areas[k].name.substring(0,17) == "description_short") || (areas[k].name.substring(0,11) == "description"))
	{ document.rowform[areas[k].name].value = tidy_html(areas[k].value);
	}
    else
	{ document.rowform[areas[k].name].value = areas[k].value;
	}
    var temp = document.rowform[areas[k].name].name;
    temp = temp.replace(/[0-9]*$/, ""); /* chance "description1" into "description" */
    document.rowform[areas[k].name].name = temp;
  }
  var selects = row.getElementsByTagName('select');
  for(let k=0;k<selects.length;k++)  
  { if(!selects[k].name) continue;
    document.rowform[selects[k].name].selectedIndex = selects[k].selectedIndex;
    var temp = document.rowform[selects[k].name].name;
    temp = temp.replace(/[0-9]*$/, ""); /* chance "description1" into "description" */
    document.rowform[selects[k].name].name = temp;
  }
  var tmp = document.getElementById('featureblock0');
  if(tmp.style.display == 'none')
    rowform.featuresset.value = 0;
  else
    rowform.featuresset.value = 1;
  if(rowform.carriersel0 && rowform.carriersel0.options[0].value == "none")
    rowform.carriersel0.options.length=0;
  rowform.verbose.value = SwitchForm.verbose.checked;
  if(SwitchForm.verbose.checked)
  { rowform.method="get";
    rowform.target="_blank";
  }
  else
  { rowform.method="post";
    rowform.target="tank";
  }  
  rowform.skipindexation.value = IndexForm.skipindexation.checked;  
  rowform.allshops.value = Mainform.allshops.value;  
  document.rowform['id_row'].value = row.childNodes[0].id;
  document.rowform.submit();
}

function reindex()
{ IndexForm.verbose.value = SwitchForm.verbose.checked;
  document.IndexForm.submit();
}

/* modify the number in "Re-index the ... unindexed products" */
function update_index(value)
{ var tmp = document.getElementById('reindexspan');
  if(tmp)
    tmp.innerHTML = value;	
}

function price_change(elt)
{ var price = elt.value;
  if((price.indexOf(',') > 0) && (price.indexOf('.') == -1))
  { price = price.replace(',','.');
	elt.value = price;
  }
  reg_change(elt);
  var VATcol = getColumn("VAT");
  if(VATcol == -1) return;
  var VAT = elt.parentNode.parentNode.cells[VATcol].innerHTML;
  var pvcol = getColumn("priceVAT");
  if(pvcol == -1) return;
  var newprice = price * (1 + (VAT / 100));
  newprice = Math.round(newprice*1000000)/1000000; /* round to 6 decimals */
  elt.parentNode.parentNode.cells[pvcol].innerHTML = newprice;
}

function priceVAT_change(elt)
{ var priceVAT = elt.value;
  if((priceVAT.indexOf(',') > 0) && (priceVAT.indexOf('.') == -1))
  { priceVAT = priceVAT.replace(',','.');
	elt.value = priceVAT;
  }
  var VATcol = getColumn("VAT");
  if(VATcol == -1) {alert("No VAT Column found!"); return; }
  var VAT = elt.parentNode.parentNode.cells[VATcol].innerHTML;
  var thisrow = elt.name.substring(8);
  var pcol = getColumn("price");
  if(pcol == -1) {alert("No price Column found!"); return; }
  var newprice = priceVAT / (1 + (VAT / 100));
  newprice = Math.round(newprice*1000000)/1000000; /* round to 6 decimals */
  elt.parentNode.parentNode.cells[pcol].innerHTML = newprice;
  var pricefield = eval("Mainform.price"+thisrow);
  pricefield.value = newprice;
  reg_change(elt);
}

function VAT_change(elt)
{ reg_change(elt);
  var col1 = getColumn("price");
  if(col1 == -1) return;
  var col2 = getColumn("priceVAT");
  if(col2 == -1) return;
  var VAT = elt.options[elt.selectedIndex].getAttribute("rate");
  var price = elt.parentNode.parentNode.cells[col1].innerHTML;
  var newpriceVAT = price * (1 + (VAT / 100));
  newpriceVAT = Math.round(newpriceVAT*1000000)/1000000; /* round to 6 decimals */
  elt.parentNode.parentNode.cells[col2].innerHTML = newpriceVAT;
}

function stockflags_change(elt)
{ reg_change(elt);
  var row = elt.parentNode.parentNode;
  var rowno = row.childNodes[0].id.substr(4);
  var warehousespan = document.getElementById("stockspan"+rowno);
  if(warehousespan) /* it is not there if the initial value was "ASM with warehousing" */
  { if(elt.value == 3)
	  warehousespan.style.display = "inline";
    else
	  warehousespan.style.display = "none";	
  }
}

/* change stockflags in the mass edit menu */
function stockflags_mass_change(elt)
{ var warehousespan = document.getElementById("stockspan");
  if(elt.value == 3)
	  warehousespan.style.display = "block";
  else
	  warehousespan.style.display = "none";	
}

function getColumn(name)
{ var tbl = document.getElementById("Maintable");
  var len = tbl.tHead.rows[0].cells.length;
  for(var i=0;i<len; i++)
  { if(tbl.tHead.rows[0].cells[i].firstChild.getAttribute("fieldname") == name)
	  return i;
  }
//  alert("Not Found Column "+name);
  return -1;
}

/* remove unmatched closing tags; close unmatched opening tags (like <div> and <b>) */
/* every time you submit a field that can contain html tags it is moved through this function */
function tidy_html(html) {
    var d = document.createElement('div');
    d.innerHTML = html;
    return d.innerHTML;
}

/* clean string and give warnings */
function check_string(myelt,taboos)
{ var patt = new RegExp( "[" + taboos + "]", "g" );
  if(myelt.value.search(patt) == -1)
    return true;
  else
  { alert("The following characters are not allowed and have been removed or replaced: "+taboos+". HTML tags have been removed as a whole.");
	let patt2 = new RegExp('<[^>]*>', 'g'); /* first remove html tags */
    myelt.value = myelt.value.replace(patt2,"");
	patt2 = new RegExp(';', 'g'); /* replace ";" with "." */
    myelt.value = myelt.value.replace(patt2,".");
	patt2 = new RegExp('{', 'g'); /* replace "{" with "[" */
    myelt.value = myelt.value.replace(patt2,"[");
	patt2 = new RegExp('}', 'g'); /* replace "}" with "]" */
    myelt.value = myelt.value.replace(patt2,"]");
    myelt.value = myelt.value.replace(patt,""); /* then remove the rest of the forbidden chars */
    return false;
  }
}

function feature_change_event(evt)
{ if(evt.key == "Tab")
	return;
  feature_change(evt.target);
}

/* take care that only one option is active at the same time */
function feature_change(elt)
{ var myform = elt;
  while (myform.nodeName != "FORM" && myform.parentNode) // find form (either massform or Mainform) 
  { myform = myform.parentNode;
  }
  if(!myform) alert("error finding form");
  if(elt.name.indexOf("_sel")>0)
  { var input = elt.name.replace("_sel","");
	myform[input].value="";
  }
  else
  { if(!check_string(elt,"<>;=#{}"))
      return;
    var patt1=/([0-9]*)$/;
    var sel = elt.name.replace(patt1, "_sel$1");
	if((elt.value != "") && myform[sel])  /* as the feature_change_event test seemed not to catch all tabs this (!="") is a second test to prevent tabs resetting the select */
	  myform[sel].selectedIndex = 0;
  }
  if(myform.name == "Mainform")
    reg_change(elt);
}

var tabchanged = 0;
function reg_change(elt)	/* register changed row so that it will be colored and only changed rows will be submitted */
{ var elts = Array();
  elts[0] = elt;
  elts[1] = elts[0].parentNode;
  var i=1;
  while (elts[i] && (!elts[i].name || (elts[i].name != 'Mainform')))
  { elts[i+1] = elts[i].parentNode;
	i++;
  }
  elts[i-4].cells[0].setAttribute("changed", "1");
  elts[i-4].style.backgroundColor="#DDD";
  tabchanged = 1;
}

/* updateblock['discounts'] = [];
   updateblock['discounts']['1'] = '914'; 
*/
function reg_unchange(rowno, updateblock)	/* change status of row back to unchanged after it has been submitted */
{ var elt = document.getElementById('trid'+rowno);
  var row = elt.parentNode;
  row.cells[0].setAttribute("changed", "0");
  row.style.backgroundColor="#AAF";
  var elt = eval('Mainform.discount_count'+rowno);
  for (var field in updateblock)
  { if(field == "discounts")
    { for (let idx in updateblock[field]) 
	  { var elu = eval('Mainform.discount_id'+idx+'s'+rowno);
	    elu.value = updateblock[field][idx];
	    elu = eval('Mainform.discount_status'+idx+'s'+rowno);
        elu.value = "update";
		/* next we make the shop and attribute inputs hidden and display them as plain text */
		var elv = eval('Mainform.discount_shop'+idx+'s'+rowno);
	    var txt = "";
	    if(elv.value==0)
	      txt = "all";
	    else
	      txt = elv.value;
	    elv.type = "hidden";
	    elv = eval('Mainform.discount_attribute'+idx+'s'+rowno);
	    if(elv.value==0)
	      txt += " all";
	    else
	      txt += " "+elv.value;
	    elv.parentNode.insertBefore(document.createTextNode(txt) ,elv);
	    elv.type = "hidden";
	  }
	}
	else if(field == "images")
    { for (let idx in updateblock[field]) 
	  { if(updateblock[field][idx] == "erased")
		{ var fld = document.getElementById("bimg"+idx);
		  fld.parentNode.removeChild(fld);
		  var imgset = eval('Mainform.image_set'+rowno);
		  imgset.value = imgset.value.replace(','+idx+',',',');
		}
		else /* rename inserted images to new image id */
		{ var newid = updateblock[field][idx];
		  var fld = document.getElementById("bimg"+idx);
		  fld.backgroundColor = "#FFFFFF";
		  fld.id = "bimg"+newid;
		  fld = eval('Mainform.image_status'+idx+'s'+rowno);
		  fld.value = 'update';
		  fld.name = 'image_status'+newid+'s'+rowno;
		  var imgset = eval('Mainform.image_set'+rowno);
		  imgset.value = imgset.value.replace(','+idx+',',','+newid+',');
		  var legend = eval('Mainform.image_legend'+idx+'s'+rowno);
		  legend.name = 'image_legend'+newid+'s'+rowno;
		  var fld2 = document.getElementById('baseimg'+idx+'s'+rowno); // we rename this not as that would cause reload
		  fld2.id = 'baseimg'+newid+'s'+rowno;
		}
	  }
	}
   }
}


var parts_stat = 0;
var desc_stat = 0;
var trioflag = false; /* check that only one of price, priceVAT and VAT is editable at a time */
function switchDisplay(id, elt, fieldno, val)  // collapse(field)
{ var tmp, tmp2, val, checked, has_combinations, change_stockflags, has_catalogue_rules, prelangroot,row;
  var fieldname, custom;
  var advanced_stock = has_combinations = change_stockflags = has_catalogue_rules = false;
/*  alternative method: faster but it doesn't reduce the height of the rows.
  var mycol = document.getElementById('col'+fieldno);
  if(val == '0') // hide 
	mycol.style.visibility = "collapse";
  else // if((val == '1') || (val=='2') || (val=='3') || (val=='4')) // 1 = show 
	mycol.style.visibility = "visible";
*/
  if(val == '0') /* hide */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++)
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='none';
  }
  if((val == '1') || (val=='2') || (val=='3') || (val=='4')) /* 1 = show */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++) 
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='table-cell'; /* undo display="none" */
  }
  if((val=='2') ||(val == '3') || (val=='4')) /* 2 = edit */
  { tab = document.getElementById('Maintable');
    var tblEl = document.getElementById(id);
    var field = tab.tHead.rows[0].cells[fieldno].children[0].getAttribute("fieldname");
	var re = /_[a-z][a-z]$/i;
	if(field.match(re))
	{ prelangroot = field.substring(0, field.length - 3);
	}
	else
	  prelangroot = field;
    if((trioflag == true) && ((field == "price") || (field == "VAT") || (field == "priceVAT") || (field == "unitPRatio") || (field == "discount")))
    { alert("You may edit only one of the following fields at a time: price, VAT, priceVAT, unitPRatio, discount");
	  var field = eval("SwitchForm.disp"+fieldno);
	  field.value = 1;
      return;
    }
    if((field == "price") || (field == "VAT") || (field == "priceVAT") || (field == "unitPRatio") || (field == "discount"))
      trioflag = true;
	else if (field == "image")
	{ var imgsuffix = '';
//	  var imgmovex = Math.round(parseInt(imgwidth)/2)-6;
//	  var imgmovey = Math.round(parseInt(imgheight)/2)-6;
	}
    else if(field=="stockflags") 
  	  change_stockflags = true;
    if((field=="image") || (field=="virtualp"))
	{ Mainform.enctype = 'multipart/form-data';
	  rowform.enctype = 'multipart/form-data';
	}
	if(prestools_missing.indexOf(field) !== -1) 
		balert("In Prestools Free the "+field+" field is in demo mode and your changes cannot be saved.\nFor full functionality buy Prestools Professional or the specific plugin at www.Prestools.com.");
	if((field.substring(0,7) == "feature") && (prestools_missing.indexOf("features") !== -1))
		balert("In Prestools Free the feature fields are in demo mode and your changes cannot be saved.\nFor full functionality buy Prestools Professional or the specific plugin at www.Prestools.com.");
    for(var i=0; i<tblEl.rows.length; i++)
    { if(!tblEl.rows[i].cells[fieldno]) continue;
	  tmp = tblEl.rows[i].cells[fieldno].innerHTML;
      tmp2 = tmp.replace("'","\'");
      row = tblEl.rows[i].cells[0].childNodes[1].name.substring(10); /* fieldname id_product7 => 7 */
	  
	  /* fields in alphabetical order: not mentioned = default */
	  if(field=="accessories")
      { tmp2 = tmp.replace(/<[^>]*>/g,'');
	    tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2.replace(/"/g, '&quot;')+'" class="extrawide" onchange="reg_change(this);" />';
	  }
      else if((field=="active") || (field=="on_sale") || (field=="online_only") || (field=="show_condition") || (field=="ls_alert"))
	  { if(tmp==1) checked="checked"; else checked="";
	    tblEl.rows[i].cells[fieldno].innerHTML = '<input type=hidden name="'+field+row+'" value="0" /><input type=checkbox name="'+field+row+'" id="'+field+row+'" onchange="reg_change(this);" value="1" '+checked+' />';
	  }
	  else if(field=="aDeliveryT")  // additional_delivery_times
	  { var tmp2 = deliverytimesblock.replace('>'+tmp+'<',' selected>'+tmp+'<');
		tblEl.rows[i].cells[fieldno].innerHTML = '<select name="aDeliveryT'+row+'"  onchange="reg_change(this);">'+tmp2;
	  }
	  else if(field=="attachmnts") 
      { tmp = tblEl.rows[i].cells[fieldno].getElementsByTagName("a");
	    var atids = [];
		for(var j=0; j< tmp.length; j++)
		  atids[j] = tmp[j].title;
	    tblEl.rows[i].cells[fieldno].innerHTML = (attachmentblock0.replace(/CQX/g, row))+attachmentblock1+(attachmentblock2.replace(/CQX/g, row));
	    fillAttachments(row,atids);
	  }
      else if(field=="availorder")
      { tblEl.rows[i].cells[fieldno].innerHTML = '<select name="availorder'+row+'" onchange="reg_change(this);">'+availorderblock.replace('>'+tmp.replace('&amp;','&')+'<', ' selected>'+tmp+'<');
	  }
	  else if(field=="carrier") 
      { var cars = new Array();
	    var tab = document.getElementById('carriers'+row);
	    if(tab)
		{ for(var y=0; y<tab.rows.length; y++)
		  {	cars[y] = tab.rows[y].cells[0].id;
		  }
		}
	    tblEl.rows[i].cells[fieldno].innerHTML = (carrierblock0.replace(/CQX/g, row))+carrierblock1+(carrierblock2.replace(/CQX/g, row));
	    fillCarriers(row,cars);
	  }
	  else if(field=="category") 
      { tblEl.rows[i].cells[fieldno].innerHTML = (categoryblock0.replace(/CQX/g, row))+categoryblock1+(categoryblock2.replace(/CQX/g, row));
	    fillCategories(row,tmp);
	  }
      else if(field=="combinations")
	  { if(tblEl.rows[i].cells[fieldno].children[0].tagName == "TABLE")
		{ tblEl.rows[i].cells[fieldno].children[0].style.display = "table";
	      if(val==3) /* if quantity editable */
		  { var atts = [];
		    var warehouseflag = tblEl.rows[i].cells[fieldno].children[0].getAttribute("data-wh");
			if(warehouseflag == 0)
			{ var mytable = tblEl.rows[i].cells[fieldno].children[0];
			  for(var y=0; y<mytable.rows.length; y++)
			  { var pa_id = mytable.rows[y].id.substring(2);
				var pa_quantity = mytable.rows[y].cells[1].innerHTML;
				mytable.rows[y].cells[1].innerHTML = "<input name='combination"+pa_id+"quantity"+row+"' value="+pa_quantity+">";
				atts.push(pa_id);
			  }
			  tblEl.rows[i].cells[fieldno].innerHTML += "<input type=hidden name=combinations"+row+" value='"+atts.join()+"'>";
			}
		  }
		}
	  }
      else if(field=="condition")
      { tblEl.rows[i].cells[fieldno].innerHTML = '<select name="condition'+row+'" onchange="reg_change(this)">'+conditionblock.replace('>'+tmp+'<', ' selected >'+tmp+'<');
      }
      else if(field=="customizations")
	  { let customizations = [];
	    let x = 0, y=0;
		let idx = 'customizations'+row;
	    let tabd = document.getElementById(idx);
		if(!tabd) alert("Miss"); /* shouldn't happen */
		tmp = '<table>';
	    if(tab)
		{ for(y=0; y<tabd.rows.length; y++)
		  {	let custid = tabd.rows[y].dataset.custid;
		    customizations[x++] = custid;
		    tmp += '<tr><td>';
			tmp += '<input type="hidden" name="custom_id'+y+'s'+row+'" value="'+custid+'">';
			tmp += '<input type="hidden" name="custom_status'+y+'s'+row+'" value="update">';
			let langtable = tabd.rows[y].cells[0].childNodes[0]; 
		    for(let z=0; z<langtable.rows.length; z++)
			{ let cell = langtable.rows[z].cells[0];
			  let custelts = cell.dataset.src.split('-');
			  tmp += '<nobr><span style="display: inline-block; width:16px">'+custelts[1]+'</span>';
			  tmp += '<textarea name="custom_name'+custelts[0]+'x'+y+'s'+row+'" onchange="reg_change(this)" rows=1 cols=30>'+cell.innerHTML.trim().replace(/&nbsp;$/,'')+'</textarea></nobr><br>';
			}
			tmp += '</td><td>';
			tmp += '<select name="custom_type'+y+'s'+row+'" onchange="reg_change(this)">';
			cell = tabd.rows[y].cells[1];
			if(cell.innerHTML == 'textfield')
			   tmp += '<option selected>textfield</option><option>uploadfile</option>';
			else if(cell.innerHTML == 'uploadfile')
			   tmp += '<option>textfield</option><option selected>uploadfile</option>';
			else
			   tmp += '<option>error</option><option>textfield</option><option>uploadfile</option>';
			tmp += '</select></td><td>';
			cell = tabd.rows[y].cells[2];
			let checked = "";
			if(cell.innerHTML == "req") 
			  checked = "checked";
			tmp += '<input name="custom_req'+y+'s'+row+'" type=checkbox '+checked+' onchange="reg_change(this)">req';
			tmp += '</td><td>';
			tmp += '<a href="#" onclick="return del_customization('+row+','+y+')"><img src="del.png"></a>';
			
			tmp += '</td></tr>';
		  }
		}
		tmp += '</table>';
		tmp = '<input type=hidden name="custom_count'+row+'" value="'+y+'">' + tmp;
		tmp += '<a href="#" onclick="return add_customization('+row+');" class="TinyLine" id="customization_adder'+row+'">Add customization</a>';
	    tblEl.rows[i].cells[fieldno].innerHTML = tmp;		  
	  }	  
      else if((prelangroot=="description") || (prelangroot=="description_short"))
      { tblEl.rows[i].cells[fieldno].innerHTML = '<textarea name="'+field+row+'" id="'+field+row+'" rows="4" cols="35" onchange="reg_change(this);" onkeyup="description_change(this)">'+tmp+'</textarea>';
		let bgcolor = '';
		let len=tmp.length;
		if(((prelangroot=="description") && (len > 21844)) ||
		  ((prelangroot=="description_short") && (psdlimit > 0) && (len > psdlimit)))
		  bgcolor = 'style="background-color:#FF8888"';
		let fld = 'dslength';  
		if(prelangroot=="description") fld = 'dlength';
		tblEl.rows[i].cells[fieldno].innerHTML += '<div class="TinyLine" '+bgcolor+'><a href="#" onclick="useTinyMCE(this, \''+field+row+'\'); return false;">TinyMCE</a>&nbsp;<a href="#" onclick="useTinyMCE2(this, \''+field+row+'\'); return false;">TinyMCE-deluxe</a><span id="'+fld+row+'">'+tmp.length+'</span></div>';
      }
	  else if(field=="discount")
      { /* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
	    /* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
		var tab = document.getElementById('discount'+row); /* this is the table */
	    if(tab)
		{ blob = "";
	      var z = 0; /* number of discounts for this product */
		  for(var y=0; y<tab.rows.length; y++)
		  { if(tab.rows[y].getAttribute("rule")== 0)
			{ blob += "<div>";
			  var newprices = tab.rows[y].cells[13].innerHTML.split('/ ');
		      blob += fill_discount(row,z,tab.rows[y].getAttribute("specid"),"update",tab.rows[y].cells[0].innerHTML,tab.rows[y].cells[1].innerHTML,tab.rows[y].cells[2].innerHTML,tab.rows[y].cells[3].innerHTML,tab.rows[y].cells[4].innerHTML,tab.rows[y].cells[5].innerHTML,tab.rows[y].cells[6].innerHTML,tab.rows[y].cells[7].innerHTML,tab.rows[y].cells[8].innerHTML,tab.rows[y].cells[9].innerHTML,tab.rows[y].cells[10].innerHTML,tab.rows[y].cells[11].innerHTML,tab.rows[y].cells[12].innerHTML,newprices[0],newprices[1]);
		      blob += "</div>";
			  tab.rows[y].innerHTML = "";
			  z++;
			}
			else
				has_catalogue_rules = true;
		  }
		  var blob = '<input type=hidden name="discount_count'+row+'" value="'+z+'">' + blob;
		  blob += '<a href="#" onclick="return add_discount('+row+');" class="TinyLine" id="discount_adder'+row+'">Add discount rule</a>';
		}
		tblEl.rows[i].cells[fieldno].innerHTML = blob;
	  }
      else if(field=="image")
      { /* note that the title looks like "33;|22|television|33|radio" with the first number being the default image id */
		if(tblEl.rows[i].cells[fieldno].innerHTML == "X")
		{ var imgdefault = 0;
		  var imparts = [];
		}
		else
		{ var tmp = tblEl.rows[i].cells[fieldno].firstChild.title;
		  var pos = tmp.indexOf(';');
		  var imgdefault = tmp.substring(0,pos);
		  var imparts = tmp.substring(pos+1).split('|');
		  if(imgsuffix == '')
		  { /* Thirty Bees uses image names like /46-community-theme-default_small_default.jpg */
			imgsuffix = tblEl.rows[i].cells[fieldno].firstChild.firstChild.src;
		    imgsuffix = imgsuffix.match(/\/[^\/]*$/);    /* result like '/12-small.jpg' */
			var tmp2 = imgsuffix[0].match(/\/[0-9]+\-/);  /* result like '/12-' */
			if(tmp2 === null)
			  imgsuffix = imgsuffix[0].match(/\.[^\.]*$/);
			else
			  imgsuffix = imgsuffix[0].substring(tmp2[0].length-1); /* result like '-small.jpg' */
		  }
		  /* not all images are present in all formats. We can't test here like we do in PHP as javascript goes over de internet */
		  /* So we take the selected image format and for the first image we take the version that was already there */
		  var firstimg = 0;
		  var tmp2 = tblEl.rows[i].cells[fieldno].getElementsByTagName("img");
		  if(tmp2.length >0)
			  firstimg = tmp2[0].src;
		}
		var str = '<table><tr>';
		str = '<div class="imgfloater" id="imgfloater'+row+'">';
		var position = 1; /* positions start with 1 */
		var images = [];
		if(tblEl.rows[i].cells[fieldno].hasAttribute('data-notinshop'))
		    var notinshops = ','+tblEl.rows[i].cells[fieldno].getAttribute('data-notinshop')+',';
		else
		    var notinshops = '';
		for (var j = 1; j < imparts.length; j++)
		{ str += '<div id="bimg'+imparts[j]+'" data-position="'+position+'" draggable="true" style="cursor:move;"';
		  str += ' ondragstart="img_dragstart(this,'+row+',event)" ondragend="img_dragend(this,'+row+',event)">';
		  str += '<input type="hidden" name="image_status'+imparts[j]+'s'+row+'" value="update">';
		  str += '<div class="imgholder">';
		  if(notinshops.indexOf(','+imparts[j]+',')==-1) /* nis=not-in-shop */
			str += '<a href="#" onclick="del_image('+row+',this); return false;"><img src="imgdelete.png" style="position:absolute; z-index:20; top:1px; right:1px"></a>';
		  else
			str += '<a href="#" onclick="del_image('+row+',this); return false;"><img src="nisimgdelete.png" style="position:absolute; z-index:20; top:1px; right:1px"></a>';
		  if(imparts[j] == imgdefault)
			str += '<a href="#" onclick="set_image_default('+row+',this); return false;"><img src="imgdefault.png" style="position:absolute; z-index:20; bottom:1px; right:1px" id="idefimg'+imparts[j]+'" title="default"></a>';
		  else
			str += '<a href="#" onclick="set_image_default('+row+',this); return false;"><img src="imgnotdefault.png" style="position:absolute; z-index:20; bottom:1px; right:1px" id="idefimg'+imparts[j]+'" title="default"></a>';	
		  str += '<img class="prodimg" id="baseimg'+imparts[j]+'s'+row+'"';
		  if((imparts[j] == imgdefault) && (firstimg != 0))
			str += ' src=\"'+firstimg+'\" />';
		  else
			str += ' src=\"'+triplepath+'img/p'+getpath(imparts[j])+'/'+imparts[j]+imgsuffix+'\" />';
		  str += '</div><textarea name="image_legend'+imparts[j]+'s'+row+'" onchange="reg_change(this);">'+imparts[j+1]+'</textarea>';
		  str += '</div>';
		  images[position-1] = imparts[j];
		  j++;
		  position++;
		}
		str += '<div class="imgadder">';
		str += '<a href="#" onclick="add_image(this,event,'+row+'); return false;">Add</a></div>';
		str += '</div>';
		var imgsetval = ','+images.join()+',';
		if(imgsetval == ',,') imgsetval = ','; /* no images */
		str = '<input type=hidden name="image_set'+row+'" id="image_set'+row+'" value="'+imgsetval+'">' + str;
		str = '<input type=hidden name="image_default'+row+'" value="'+imgdefault+'">' + str;
		str = '<input type="file" accept="image/*" id="imgcollector'+row+'" class="imgcollector" multiple onchange="imgcollector_change(this,'+row+')">'+str;
		tblEl.rows[i].cells[fieldno].innerHTML = str;
	  }
      else if(field=="manufacturer") 
      { tblEl.rows[i].cells[fieldno].innerHTML = '<select name="manufacturer'+row+'" onchange="reg_change(this);">'+manufacturerblock.replace('>'+tmp.replace('&amp;','&')+'<', ' selected>'+tmp+'<');
      }
	  else if(field=="meta_description")
        tblEl.rows[i].cells[fieldno].innerHTML = '<textarea name="'+field+row+'" rows="4" cols="35" onchange="reg_change(this);">'+tmp+'</textarea>';
	  /* field == on_sale / field = online_only: see active */
	  /* field == name: see accessories */
	  else if(field=="name")
	  { tmp2 = tmp.replace(/<[^>]*>/g,'');
	    tblEl.rows[i].cells[fieldno].innerHTML = '<input name="name'+row+'" value="'+tmp2.replace(/"/g, '&quot;')+'" class="extrawide" onchange="name_change(\''+row+'\'); reg_change(this);" />';
	  }
	  else if(field=="out_of_stock")
	  { // if(tblEl.rows[i].cells[0].getAttribute("combix") == "0")
	      tblEl.rows[i].cells[fieldno].innerHTML = '<select name="out_of_stock'+row+'" onchange="reg_change(this)">'+out_of_stockblock.replace('>'+tmp, ' selected>'+tmp);
      }
      else if(field=="price")
        tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" onchange="price_change(this)" />';
      else if(field=="priceVAT") 
        tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" onchange="priceVAT_change(this)" /><input type=hidden name="price'+row+'" value="'+tblEl.rows[i].cells[fieldno-2].innerHTML+'">';
      else if(field=="pack_stock_type")
      { tblEl.rows[i].cells[fieldno].innerHTML = '<select name="pack_stock_type'+row+'" onchange="reg_change(this)">'+pack_stock_typeblock.replace('>'+tmp+'<', ' selected >'+tmp+'<');
      }	 
	  else if(field=="quantity")
	  { if(tblEl.rows[i].cells[fieldno].style.backgroundColor != "")
	    { if(tblEl.rows[i].cells[fieldno].style.backgroundColor == "yellow")
		    advanced_stock = true;
		  else /* combinations */
			has_combinations = true;
		  continue;
		}
	    tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" onchange="reg_change(this);" />';
	  }
      else if(field=="redirect")
      { var mycell = tblEl.rows[i].cells[fieldno];
		if(tmp.indexOf('<'))
		  tmp = tmp.substr(0,tmp.indexOf('<'));
		var txt = '<select name="redirect_type'+row+'" onchange="reg_change(this)">'+redirectblock.replace('>'+tmp+'<', ' selected >'+tmp+'<');
		txt += '<br>Target <input name="id_redirected'+row+'" value="'+mycell.dataset.id+'" size=2>';
        mycell.innerHTML = txt;
	  }
      else if(field=="shopz")
      { var shopz = tmp2.split(",");
		var myshops = shop_ids.split(","); 
		tmp = '';
		for(var x=0; x<myshops.length; x++)
		{ var checked = '';
		  if(inArray(myshops[x],shopz))
			 checked = 'checked';
		  tmp += '<input type="checkbox" name="shopz'+row+'[]" value='+myshops[x]+' '+checked+' onchange="reg_change(this);"> '+myshops[x]+'<br>';
        }
		tblEl.rows[i].cells[fieldno].innerHTML = tmp;
      }
	  
      else if(field=="stockflags") 
      { var warning = "For packs this field cannot be edited";
        if (tmp.substring(0,warning.length) != warning)
		{ tblEl.rows[i].cells[fieldno].innerHTML = '<select name="stockflags'+row+'" onchange="stockflags_change(this);">'+stockflagsblock.replace('>'+tmp.replace('&amp;','&')+'<', ' selected>'+tmp+'<');
		  if(tmp != "ASM with Warehousing")
		  { if (tblEl.rows[i].cells[fieldno].getAttribute("haswarehouses") == "0")
			  tblEl.rows[i].cells[fieldno].innerHTML += '<br><span id=stockspan'+row+' style="display:none">Move stock of shop(s) to warehouse:<br><select name=stockflags_warehouse'+row+'>'+warehouseblock+'</span>';
			else	/* with the stock_reinstate flag we exclude the case that the previous state was also ASM with WH */
			  tblEl.rows[i].cells[fieldno].innerHTML += '<br><input type=hidden name="stock_reinstate'+row+'" value="yes"><span id=stockspan'+row+' style="display:none">Existing warehouses will be reinstated</span>';
		  }
	    }
	  }
      else if(field=="supplier") 
      { var trow = document.getElementById("trid"+row).parentNode;
  	    var sups = trow.cells[fieldno].getAttribute("sups");
	    var attrs = trow.cells[fieldno].getAttribute("attrs");
	    var default_supplier = trow.cells[fieldno].getAttribute("def_supplier");		
	  
		var blob = '<input type=hidden name="supplier_attribs'+row+'" value="'+attrs+'">';
		blob += '<input type=hidden name="old_suppliers'+row+'" value="'+sups+'">';
	    blob += (supplierblock0.replace(/CQX/g, row))+supplierblock1+(supplierblock2.replace(/CQX/g, row));

	    var attributes = attrs.split(",");
		
		for(var a=0; a< attributes.length; a++)
		{ var tab = document.getElementById("suppliers"+attributes[a]+"s"+row);
		  blob += '<table id="suppliertable'+attributes[a]+'s'+row+'" class="suppliertable" title="'+tab.title+'">';
		  if(tab)
		  { var first = 0;
	        for(var y=0; y<tab.rows.length; y++)
		    { blob += '<tr><td class="'+tab.rows[y].cells[0].className+'">'+tab.rows[y].cells[0].innerHTML+'</td>';
			  blob += '<td><input name="supplier_reference'+attributes[a]+'t'+tab.rows[y].title+'s'+row+'" value="'+tab.rows[y].cells[1].innerHTML.replace(/"/g, '&quot;')+'" onchange="reg_change(this);"></td>';
			  blob += '<td><input name="supplier_price'+attributes[a]+'t'+tab.rows[y].title+'s'+row+'" value="'+tab.rows[y].cells[2].innerHTML.replace(/"/g, '&quot;')+'" onchange="reg_change(this);"></td>';
			  blob += '<td><select name="supplier_currency'+attributes[a]+'t'+tab.rows[y].title+'s'+row+'" onchange="reg_change(this);">'+currencyblock.replace(">"+tab.rows[y].cells[3].innerHTML+"<"," selected>"+tab.rows[y].cells[3].innerHTML+"<")+'</select></td>';
			  if(first++ == 0) 
			  { blob += '<td rowspan="'+tab.rows.length+'">'+tab.rows[y].cells[4].innerHTML+'</td>';
			  }
			  blob += '</tr>';
			}
		  }
		  blob += '</table>';
		}
		trow.cells[fieldno].innerHTML = blob;
		var list = document.getElementById('supplierlist'+row);
		var sel = document.getElementById('suppliersel'+row);
		var suppliers = sups.split(",");
		for (var x=0; x< suppliers.length; x++)
		{ for(var y=0; y< list.length; y++)
		  { if(list.options[y].value == suppliers[x])
			{ list.selectedIndex = y;
			  var selrow = Addsupplier(row,0);
			  if(suppliers[x] == default_supplier)
				  sel.options[selrow].className = "defcat";
			}	
		  }
		}
		var def_supplier_fld = eval("document.Mainform.supplier_default"+row);
		def_supplier_fld.value = '0'; /* '0' stands for unchanged */
	  }
      else if(prelangroot=="tags")
      { tmp = tmp.replace(/<\/?nobr>/gi, "");
	    tmp = tmp.replace(/\<br>/gi, ",");
	    tblEl.rows[i].cells[fieldno].innerHTML = '<textarea name="'+field+row+'" rows="4" cols="25" onchange="reg_change(this);">'+tmp+'</textarea>';
	  }
	  else if(field=="unitPRatio") 
	  { var col = getColumn("price");
  /* note that unitPRatio is a calculated value. The database field is unit_price_ratio. To calculate that we need the price field. */
        tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'"  onchange="reg_change(this);" /><input type=hidden name="price'+row+'" value="'+tblEl.rows[i].cells[col].innerHTML+'">';
      }
      else if(field=="VAT")
      { tmp = tblEl.rows[i].cells[fieldno].getAttribute("idx");
		if(missing_taxgroups[parseInt(tmp)])
		  tblEl.rows[i].cells[fieldno].innerHTML = '<select name="VAT'+row+'" onchange="VAT_change(this)">'+taxblock.replace('</select>','')+missing_taxgroups[parseInt(tmp)]+'</select>';
	    else 
          tblEl.rows[i].cells[fieldno].innerHTML = '<select name="VAT'+row+'" onchange="VAT_change(this)">'+taxblock.replace('value="'+tmp+'"', 'value="'+tmp+'" selected');
      }
	  else if(field=="virtualp")
      { var mycell = tblEl.rows[i].cells[fieldno];
		if(mycell.style.backgroundColor != "") continue; /* prods with combinations cannot be virtual */
		var myrow = mycell.childNodes[0].rows[1];		
		var filecell = myrow.cells[1].innerHTML;
		if(myrow.cells[1].innerHTML != '') /* if there is a file */
		{ filecell += ' <nobr><input type="hidden" name="dl_delete'+row+'" id="dl_delete'+row+'" value="0">';
		  filecell += '<input type=checkbox name="dl_delete'+row+'" onchange="virtual_delete(this);" value="1"> delete</nobr><br><span id="dl_span'+row+'" style="display:none">';
		  myrow.cells[2].style.display = "table-cell";
		  mycell.childNodes[0].rows[0].cells[2].style.display = "table-cell"; /* the "name" header */
		}
	    myrow.cells[2].innerHTML = '<textarea name="dl_display_filename'+row+'" cols=20 rows=2 onchange="reg_change(this);" />'+myrow.cells[2].innerHTML+'</textarea>';
		myrow.cells[3].innerHTML = '<input name="date_expiration'+row+'" value="'+myrow.cells[3].innerHTML+'" size=8 onchange="reg_change(this);" />';
		myrow.cells[4].innerHTML = '<input name="nb_days_accessible'+row+'" value="'+myrow.cells[4].innerHTML+'"  onchange="reg_change(this);" style="width:45px;" />';
		myrow.cells[5].innerHTML = '<input name="nb_downloadable'+row+'" value="'+myrow.cells[5].innerHTML+'"  onchange="reg_change(this);" style="width:45px;" />';
		var checked = "";
		if(myrow.cells[6].innerHTML=="1") checked = " checked";
		myrow.cells[6].innerHTML = '<input type=hidden name="dl_active'+row+'" value="0">';
		myrow.cells[6].innerHTML += '<input type=checkbox id="dl_active'+row+'" name="dl_active'+row+'" '+checked+' value="1" onchange="reg_change(this);" />';
		filecell += '<input type="file" name="dl_upload'+row+'" onchange="upload_change(this);">';
		if(myrow.cells[1].innerHTML != '') filecell += '</span>';
		myrow.cells[1].innerHTML = filecell;
		if(myrow.cells[0].innerHTML==1) checked="checked"; else checked="";
	    myrow.cells[0].innerHTML = '<input type=hidden name="is_virtual'+row+'" value="0" />';
		myrow.cells[0].innerHTML += '<input type=checkbox name="is_virtual'+row+'" id="is_virtual'+row+'" onchange="virtual_change(this);" value="1" '+checked+' />';
      }
      else if(field=="visibility")
      { tblEl.rows[i].cells[fieldno].innerHTML = '<select name="visibility'+row+'" onchange="reg_change(this)">'+visibilityblock.replace('>'+tmp+'<', ' selected >'+tmp+'<');
      }
	  else if(field.substring(0,7) == "feature") /* features */
      { var rx = /\d+/;
        var fieldnr = field.match(rx);
		var pos = featurekeys.indexOf(fieldnr.toString());
		if(pos == -1) alert("problem finding Feature key");
		fieldname = field;
	    if(!multi_feature)	/* for older PS versions */
		{ if(tmp.match("<b>")) 
		  { custom = 0; /* "custom" = in dropdown select */
		    tmp = tmp.replace(/<[^>]*>/g,"");
		    tmp3 = "";
		  }
		  else
		  { custom=1;
		    tmp3 = tmp2;
		  }
		  if((val == 2) || (val == 4)) /* note that we will only use it for val==4 when there is no-custom content */
		    inserta = ' <input name="'+fieldname+row+'" value="'+tmp3.replace(/"/g, '&quot;')+'" onkeyup="feature_change_event(event);" />';
		  else if(val == 3)
		    inserta = ' <textarea name="'+fieldname+row+'" id="'+fieldname+row+'" rows="4" cols="35" onkeyup="feature_change_event(event);">'+tmp3+'</textarea>';

	      if(featureblocks[pos] == "")
			tblEl.rows[i].cells[fieldno].innerHTML = inserta;
		  else if ((custom == 0) || ((val==4) && (tmp2 == "")))
		  { if(val == 4) /* no input */
		      tblEl.rows[i].cells[fieldno].innerHTML = '<select name="'+fieldname+'_sel'+row+'" onchange="reg_change(this)" >'+featureblocks[pos].replace('>'+tmp+'<', ' selected>'+tmp+'<')+'<input type=hidden name="'+fieldname+row+'" value="" />';
		  else
		    tblEl.rows[i].cells[fieldno].innerHTML = '<select name="'+fieldname+'_sel'+row+'" onchange="feature_change(this)">'+featureblocks[pos].replace('>'+tmp+'<', ' selected>'+tmp+'<')+inserta;
		  }
		  else if (custom == 1)
		  {  tblEl.rows[i].cells[fieldno].innerHTML = '<select name="'+fieldname+'_sel'+row+'" onchange="feature_change(this)">'+featureblocks[pos]+inserta;
		  }
		  else // custom=-1 => field not set
		  {  tblEl.rows[i].cells[fieldno].innerHTML = '<select name="'+fieldname+'_sel'+row+'" onchange="feature_change(this)">'+featureblocks[pos]+inserta;
		  }
		}
		else  /* PS 1.7.3+/TB 1.3+: multi_feature */
		{ if(tmp=="")
			featrcount = 0;
		  else
		  { var featrs = tmp.split("<br>");
		    var featrcount = featrs.length;
		  }
		  var contentblock = '<td rowspan=2><table id='+fieldname+'content'+row+'><tbody>';
		  for(var x=0; x<featrcount; x++)
		  { contentblock += '<tr><td>';
	        if(featrs[x].match("<b"))
			{ var fid = featrs[x].substr(10).match(/[0-9]*/)[0]; /* id is in the title field of <b> */
			  contentblock += featrs[x];
			}
			else
			{ var fid = featrs[x].substr(13).match(/[0-9]*/)[0]; /* id is in the title field of <span> */
			  var txt = featrs[x].match(/>[^<]*</);
			  txt = txt[0].substring(1,txt[0].length-1);
			  if (val == '3')
			    contentblock += '<textarea name='+fieldname+x+'t'+row+' rows=2 cols=20>'+txt+'</textarea>';
              else
			    contentblock += '<input name='+fieldname+x+'t'+row+' value="'+txt+'">';
			}
		    contentblock += '<input type=hidden name='+fieldname+x+'s'+row+' value="'+fid+'">';
		    contentblock += '</td><td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"/></a></td></tr>'; /* note that if we call reg_change after del_feature "this" will be invalid */
		  }
		  contentblock += '</tbody></table></td>';
		  var inserta = '<input type=hidden name='+fieldname+'_count'+row+' value='+featrcount+'>';
		  inserta += '<table><tr><td>';
		  if(featureblocks[pos] != "") /* if there are preset values provide them here */
		  { inserta += '<select id="'+fieldname+'_sel'+row+'" >'+featureblocks[pos]+'</select>';
            inserta += '</td><td><a href="#" onclick="add_feature('+row+',\''+fieldname+'\',1); reg_change(this);"><img src="add.gif" border="0"></a></td>';
            inserta += contentblock;
		    inserta += '</tr><tr><td>';
		  }
		  if (val == '3')
			inserta += '<textarea id='+fieldname+'_input'+row+' rows=2 cols=20></textarea>';
          else if (val == '2')
		    inserta += '<input id='+fieldname+'_input'+row+'>';
		  inserta += '</td>';
		  if(val == '4')
			inserta += '<td></td>';
		  else
		    inserta += '<td><a href="#" onclick="add_feature('+row+',\''+fieldname+'\','+val+'); reg_change(this);"><img src="add.gif" border="0"></a></td>';
		  if(featureblocks[pos] == "") /* when no select box */
			  inserta += contentblock;
		  inserta += '</tr></table>';
		  tblEl.rows[i].cells[fieldno].innerHTML = inserta;
		}
      }
      else  /* all the fields that don't need special treatment */
      { if((((field!="location") || (prestashop_version < "1.7.5")) && (field!="ls_alert") && (field!="minimal_quantity") && (field!="ls_threshold") && (field!="available_date")) || (tblEl.rows[i].cells[0].getAttribute("combix") == "0"))
		  tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2.replace(/"/g, '&quot;')+'" onchange="reg_change(this);" />';
	  }
    }
	/* now we adapt the switchblock (hide-show-edit); with "edit" hide and show are no longer avialable and we may add + and - for resizing the field */
	if((field.substring(0,7) !== "feature") && (field != "combinations")) /* if not a feature and not an image */
		var cell = elt.parentElement; /* td cell */
	else
		var cell = elt.parentElement.parentElement;
	tmp = cell.innerHTML.replace(/<br.*$/,'');  /* remove everything except for the field name */
	var blockfields = ["description","description_short","meta_description","customizations"];
	var linefields = ["meta_keywords","meta_title","name","ean","upc","discount"];	
	if(blockfields.indexOf(field) >= 0)
	{ TMCE_loadonce();
 	  cell.innerHTML = tmp+'<br>Edit<br><img src=minus.png title="make field less high" onclick="grow_textarea(\''+field+'\','+fieldno+', -1, 0);"><b>H</b><img src=plus.png title="make field higher" onclick="grow_textarea(\''+field+'\','+fieldno+', 1, 0);"><br><img src=minus.png title="make field less wide" onclick="grow_textarea(\''+field+'\','+fieldno+', 0, -7);"><b>W</b><img src=plus.png title="make field wider" onclick="grow_textarea(\''+field+'\','+fieldno+', 0, 7);">';	
	}
	else if(linefields.indexOf(field) >= 0)
	  cell.innerHTML = tmp+'<br>Edit<br><nobr><img src="minus.png" title="make field less wide" onclick="grow_input(\''+field+'\','+fieldno+', -7);"><b>W</b><img src="plus.png" title="make field wider" onclick="grow_input(\''+field+'\','+fieldno+', 7);"></nobr>';
	else if(field == "image")
 	{ cell.innerHTML = tmp+'<br>Edit<br><img src=minus.png title="make field less high" onclick="grow_image(\''+val+'\','+fieldno+', -20, 0);"><b>H</b><img src=plus.png title="make field higher" onclick="grow_image(\''+val+'\','+fieldno+', 20, 0);"><br><img src=minus.png title="make field less wide" onclick="grow_image(\''+val+'\','+fieldno+', 0, -20);"><b>W</b><img src=plus.png title="make field wider" onclick="grow_image(\''+val+'\','+fieldno+', 0, 20);">';	
	}
	else if ((typeof fieldname !== 'undefined') && (fieldname == "feature"+featurekeys[pos]+"field"))
	{ if(val == 2)
		cell.innerHTML = tmp+'<br>Edit<br><img src="minus.png" title="make field less wide" onclick="grow_input(\''+fieldname+'\','+fieldno+', -7);"><b>W</b><img src="plus.png" title="make field wider" onclick="grow_input(\''+fieldname+'\','+fieldno+', 7);">';	
	  else if(val == 3) /* val==3 */
		cell.innerHTML = tmp+'<br>Edit<br><img src=minus.png title="make field less high" onclick="grow_textarea(\''+fieldname+'\','+fieldno+', -1, 0);"><b>H</b><img src=plus.png title="make field higher" onclick="grow_textarea(\''+fieldname+'\','+fieldno+', 1, 0);"><br><img src=minus.png title="make field less wide" onclick="grow_textarea(\''+fieldname+'\','+fieldno+', 0, -7);"><b>W</b><img src=plus.png title="make field wider" onclick="grow_textarea(\''+fieldname+'\','+fieldno+', 0, 7);">';	
	  else  /* val==4 */
		cell.innerHTML = tmp+'<br><br>Edit';	
	}
	else
	  cell.innerHTML = tmp+"<br><br>Edit";
  }
  if(field=="image")
  { alert("Just like other functions in Prestools image changes are only implemented after you submit.\nWhen adding images only the basic image is added: you need to regenerate images afterwards. SubmitAll leads you to the regenerate page. WIth SubmitRow there is an IR link in the frame.");
    if(search_form.imgformat.selectedIndex == 0)
	  alert("When editing pictures you are advised to choose one of the larger image formats at the top of the page.");
  }
  if(field=="carrier")
	  alert("By default all carriers are available for a product. This function is for products that need special carriers for example because they are too big or need installation.");
  if((val >= 2) && (field=="image"))
    setTimeout(prepare_imgdrop, 100);
  var warning = document.getElementById("warning").innerHTML;
  if(advanced_stock)
    warning += "Quantity fields of products with warehousing - marked in yellow - cannot be changed.<br>";
  if(has_combinations)
    warning += "Quantity fields for products with combinations - marked in red - cannot be changed here.<br>";
  if(change_stockflags)
  { warning += "In order to automate changing stockflags in mass edit shortcuts and a reduction of flexibility compared to the PS back office was necessary.<br>";	
	warning += "Consult the manual for more information.<br>";	
  } 
  if(has_catalogue_rules)
  { warning += "Discount rules that cannot be changed result from catalogue rules.<br>";	 
  }
  var tmp = document.getElementById("warning");
  tmp.innerHTML = warning;
  return;
}

function description_change(elt)
{ var row = getrow(elt);
  var name=elt.name;
  var fld;
  if(name.substring(0,17) == "description_short")
    fld = document.getElementById("dslength"+row); 
  else
    fld = document.getElementById("dlength"+row); 
  if(((name.substring(0,17) == "description_short") && (psdlimit > 0) && (elt.value.length >psdlimit)) || 
	 ((name.substring(0,11) == "description") && (elt.value.length >21844)))
  	 fld.parentNode.style.backgroundColor = "#FF8888";
  else
  	 fld.parentNode.style.backgroundColor = "#FFFFFF"; 
  fld.innerText = elt.value.length; 
}

function getrow(element)
{ var elt = element;
  while (elt.tagName != "TR") 
  { elt = elt.parentNode;
  }
  return elt.childNodes[0].id.substring(4);
}

function add_customization(row)
{ var count_root = eval('Mainform.custom_count'+row);
  var ccount = parseInt(count_root.value);
  var blob = '<td><input type="hidden" name="custom_id'+ccount+'s'+row+'" value="">';
  blob += '<input type="hidden" name="custom_status'+ccount+'s'+row+'" value="new">';
  var langids = lang_ids.split(',');
  var langcodes = lang_codes.split(',');
  var len = langids.length;

  for(var i=0; i<len; i++)
  { blob += '<span style="display: inline-block; width:16px">'+langcodes[i]+'</span><textarea name="custom_name'+langids[i]+'x'+ccount+'s'+row+'" onchange="reg_change(this)" rows="1" cols="30"></textarea><br>';
  }
  blob += '</td><td><select name="custom_type'+ccount+'s'+row+'" onchange="reg_change(this)"><option>textfield</option><option>uploadfile</option></select></td><td><input name="custom_req'+ccount+'s'+row+'" type="checkbox" onchange="reg_change(this)">req</td><td><a href="#" onclick="return del_customization('+row+','+ccount+')"><img src="del.png"></a></td>';
  
  var tabs = count_root.parentNode.getElementsByTagName('TABLE');
  var newRow = tabs[0].insertRow(-1);
  newRow.innerHTML = blob;
  count_root.value = ccount+1;
  return false;
}

function del_customization(row,entry)
{ var field = eval('Mainform.custom_status'+entry+'s'+row);
  field.value = 'deleted';
  var inputs = field.parentNode.getElementsByTagName('INPUT');
  var len = inputs.length;
  var blub = '';
  for(var i=0; i<len; i++)
    blub += inputs[i].outerHTML;
  var tr = field.parentNode.parentNode;
  tr.innerHTML = '<td>'+blub+'</td>';
  reg_change(tr);
  return false;
}


/* Drag functions:
 * Events fired on the draggable target (the source element): ondragstart, ondrag, ondragend
 * Events fired on the drop target: ondragenter, ondragover, ondragleave, ondrop
 */
var draggedimgrow = -1; /* tells whether the td should look for imported files or images that are dragged to another sorting place */
var img_upload_index = 0; /* will increase for each uploaded file */
var uploadingImages = new Set(); /* list of image indexes in the process of uploading */
var request_array = [];
//var img_upload_array = []; /* this is an array of arrays with fields:
//  0=row; 1=file; 2=filename; 3=altfilename; 4=status(0=uploading/1=completed) */

/* prepare_imgdrop() is called once when images are made editable */
function prepare_imgdrop()
{ var elts = Array.from(Maintable.getElementsByClassName("imgcollector"));
  elts.forEach((elt) => 
	  { mytd = elt.parentNode;
	    mytd.addEventListener('dragover', td_img_dragover);
		mytd.addEventListener('drop', td_img_drop);
		mytd.addEventListener('dragenter', td_img_dragenter);
		mytd.addEventListener('dragleave', td_img_dragleave);		
	  }
  );
}

function td_img_dragover(ev) 
{   if(draggedimgrow != -1)
	{ var myrow = getrow(ev.target);
	  if(draggedimgrow != myrow)
	  {	return true;
	  }
	}
    ev.preventDefault(); /* a call to ev.preventDefault() makes an area draggable */
	ev.stopPropagation();
}

function td_img_dragenter(ev) 
{   ev.preventDefault();
	ev.stopPropagation();
}

function td_img_dragleave(ev) 
{   ev.preventDefault();
	ev.stopPropagation();
}

function td_img_drop(ev)
{ ev.preventDefault();
  ev.stopPropagation();
  if(draggedimgrow != -1)
  { drop_image(ev);
  }
  else /* drop or select from filesystem */
  { var dt = ev.dataTransfer;
    var files = dt.files;
	var row = getrow(ev.target);
    var filelist = Array.from(files);
    for(var i=0; i<filelist.length; i++)
	  upload_image_file(filelist[i],row);
  }
}

function upload_image_file(file, row)
{ var filename = file.name;
  if(file.size > maxprodimgsize)
  { alert("This file has size "+file.size+". According to your Prestools settings the maximum allowed size is "+ maxprodimgsize);
    return;
  }
  if(filename.indexOf('.') == -1) return;
  var suffix = filename.substring(filename.lastIndexOf('.')+1, filename.length).toLowerCase();
  var allowedSuffixes = ["jpg","png","gif","jpeg"];
  if(allowedSuffixes.indexOf(suffix) == -1) return;
  var imgidx = img_upload_index++;
  
  var target = document.getElementById("imgfloater"+row);
  var targetpos = target.childNodes.length; /* position starts at 1: the "Add" block is always there. */
  
  var legend = "";
  if(targetpos == 1) /* first image */
  { var namecol = getColumn("name");
    if(namecol != -1)
	{ var tblEl = document.getElementById("offTblBdy");
	  legend = tblEl.rows[row].cells[namecol].innerHTML;
      if(legend.substring(0,2) == "<i")  /* if edit mode */
	  { var fld = eval('Mainform.name'+row);
	    legend = fld.value;
	  }
	  else
		legend = striptags(legend);
	}
  }
  
  var tmp = '<div class="newimg" id="bimgn'+imgidx+'" data-position="'+targetpos+'" draggable="true"';
  tmp += ' ondragstart="img_dragstart(this,'+row+',event)" ondragend="img_dragend(this,'+row+',event)">';
  tmp += '<input type="hidden" name="image_statusn'+imgidx+'s'+row+'" value="uploading">';
  tmp += '<input type="hidden" name="image_namen'+imgidx+'s'+row+'" value="">';
  tmp += '<div class="imgholder">';
  tmp += '<a href="#" onclick="del_image('+row+',this); return false;">';
  tmp += '<img src="imgdelete.png" style="position:absolute; z-index:20; top:3px; right:3px"></a>';
  tmp += '<a href="#" onclick="set_image_default('+row+',this); return false;">';
  if(targetpos == 1)
    tmp += '<img src="imgdefault.png" id="idefimgn'+imgidx+'" title="default"';
  else
    tmp += '<img src="imgnotdefault.png" id="idefimgn'+imgidx+'" title="default"';
  tmp += ' style="position:absolute; z-index:20; bottom:3px; right:3px"></a>';
  tmp += '<div class="imgupload_progress"><span class="imgupload_progressbar" style="width: 100%;"></span></div>';
  tmp += '</div>';
  tmp += '<textarea name="image_legendn'+imgidx+'s'+row+'" onchange="reg_change(this);">'+legend+'</textarea></div>';
  var divtmp = document.createElement('div');
  divtmp.innerHTML = tmp;
  target.insertBefore(divtmp.childNodes[0],target.childNodes[targetpos-1]);
  
  if(targetpos == 1)
  { var image_default_root = eval("Mainform.image_default"+row);
	image_default_root.value = 'n'+imgidx;
  }
  
  var image_set_root = document.getElementById("image_set"+row);
  image_set_root.value = image_set_root.value+"n"+imgidx+",";
  uploadingImages.add(imgidx);
  
  var formData = new FormData();
  formData.append("userfile", file);
  var request = new XMLHttpRequest();
  request_array[imgidx] = request;
  request.open("POST", "imgupload.php");
  request.onload = function () {
	var eltx = document.getElementById("bimgn"+imgidx);
	var elts = eltx.querySelectorAll(".imgholder");
	var elt = elts[0];
	if (request.status === 200) { // successfully uploaded
//		img_upload_array[imgidx].uploadedName = request.responseText;
		if(!eltx) return; /* image may have been deleted before upload completed */
		tmp = eval('Mainform.image_statusn'+imgidx+'s'+row);
		tmp.value = 'insert';
		tmp = eval('Mainform.image_namen'+imgidx+'s'+row);
		tmp.value = request.responseText;
		elt.insertAdjacentHTML("beforeend",'<img class="prodimg" id="baseimgn'+imgidx+'s'+row+'" src="temp/'+request.responseText+'" onload="checkuploadimg(this)">');
		var bar = elt.getElementsByClassName("imgupload_progress");
		bar[0].parentNode.removeChild(bar[0]);
		recalculate_imgdefault(row);
		uploadingImages.delete(imgidx);
	}
	else { // error during upload
		var elt = document.getElementById("bimgn"+imgidx);
		elt.style.backgoundColor = '#FFAAAA';
		var msg = 'Error '+request.status+'<br>'+request.responseText;
		elt.innerHTML = msg;
		alert(msg.replace('<br>','\n'));
		uploadingImages.delete(imgidx);
	}

  };
    request.upload.onprogress = function (elt) {
	if(!elt) return; /* image may have been deleted before upload completed */
	var percents = Math.ceil(event.loaded * 100 / event.total);
	setProgress(imgidx, percents);
  };
  request.send(formData);
  reg_change(target);
}

function setProgress(imgidx,progress) 
{ var elt = document.getElementById("bimgn"+imgidx);
  var bar = elt.getElementsByClassName("imgupload_progressbar");
  bar[0].parentNode.style.visibility = "visible";
  bar[0].style.width = progress + "%";
}

function checkuploadimg(elt)
{ var mystyle = getComputedStyle(elt);
  var width = mystyle.width.replace('px','');
  var height = mystyle.height.replace('px',''); 
  if(width/height > elt.naturalWidth/elt.naturalHeight)
  {	elt.className="prodimg2";
  }
  else
  { elt.className="prodimg1";
	var margin = (height - (width/elt.naturalWidth * elt.naturalHeight))/2;
	margin = parseInt(margin);
 	elt.style.paddingTop = margin+'px';
  }
}

function recalculate_imgdefault(row)
{ var row = parseInt(row);
  var image_dft_root = eval("Mainform.image_default"+row);
  var image_dft = image_dft_root.value;
  if(image_dft == -1)
  { var image_set_root = eval("Mainform.image_set"+row);
    var image_set = image_set_root.value;
    var imgs = image_set.substring(1).split(","); /* this will leave an "empty" image at the end */
    var len = imgs.length-1;
	for(i=0; i<len; i++)
	{ var image_status_root = eval("Mainform.image_status"+imgs[i]+"s"+row);
	  if(image_status_root.value != "deleted")
	  { image_dft_root.value = imgs[i];
		var dftimg = document.getElementById("idefimg"+imgs[i]);
		dftimg.src = "imgdefault.png";
		return;
	  }
	}
  }
}

function img_dragstart(elt,row,ev) 
{ /* the main area was already made droppable when images became editable */
  var floaterdiv = elt.parentNode;
  var len = floaterdiv.childNodes.length;
  draggedimgrow = row; 
  for(var i=0; i<len; i++)
  { floaterdiv.childNodes[i].addEventListener('dragover', td_img_dragover);
    floaterdiv.childNodes[i].addEventListener('drop', drop_image);
  }
  
  var imgid = elt.id.substring(4);
  ev.dataTransfer.setData('text', imgid+"-"+row);
}

function img_dragend(elt,row,ev) 
{ elt = ev.target;
  var floaterdiv = elt.parentNode;
  var len = floaterdiv.childNodes.length;
  draggedimgrow = -1;
  for(var i=0; i<len; i++)
  { floaterdiv.childNodes[i].removeEventListener('dragover', td_img_dragover);
    floaterdiv.childNodes[i].removeEventListener('drop', drop_image);
  }
}

function drop_image(ev) 
{   ev.preventDefault();
	ev.stopPropagation();
	var target = ev.target;
	var olddata = ev.dataTransfer.getData('text').split("-");
	var dropimg = olddata[0];
	var row = olddata[1];
	var oldblock = document.getElementById("bimg"+dropimg);
	var oldpos = oldblock.getAttribute("data-position")-1;
	
	var elts = [];
	var i=0;
	while(target.className != "imgfloater")
	{ elts[i++] = target;
	  target = target.parentNode;
	  if(i>4) break;
	}
	if((i>4) || (elts[i-1].className == "imgadder")) /* dropped in td outside the other images */
	{ target = document.getElementById("imgfloater"+row);
	  targetpos = target.childNodes.length-1;
	}
	else
	{ //var imgid = elts[i-1].id.substring(4);  /* like "bimg123" */
      var targetpos = elts[i-1].getAttribute("data-position")-1;
	}

	if(targetpos == oldpos) return;

    var remelt = target.removeChild(target.childNodes[oldpos]);
	if(targetpos > oldpos) targetpos--; /* the removal decreases all following numbers */
	target.insertBefore(remelt,target.childNodes[targetpos]);
	
	/* now reset the position numbers */
	var imgfld = eval("Mainform.image_set"+row);
	var imgs = imgfld.value.substring(1).split(",");
	
	if(targetpos > oldpos)
	{ var start = oldpos; var end = targetpos; }
	else
	{ var start = targetpos; var end = oldpos; }

	// var len = target.childNodes.length;
	for(var j=start; j<=end; j++)
	{ imgs[j] = target.childNodes[j].id.substring(4);
	  target.childNodes[j].setAttribute("data-position",j+1);
	}
	imgfld.value = ","+imgs.join();
	reg_change(target);
}

function del_image(row,elt)
{ var imgid = elt.parentNode.parentNode.id.substring(4);
  var statusfield = eval('Mainform.image_status'+imgid+'s'+row);
  var status = statusfield.value;
  var img = document.getElementById("baseimg"+imgid+"s"+row);
  if(status == "update")
  { statusfield.value = "deleted";
	img.style.opacity = 0.3;
  }
  else if(status == "deleted")
  { statusfield.value = "update";
	img.style.opacity = 1;
  }
  else if(status == "insert") /* remove freshly inserted image */
  { var name = eval('Mainform.image_name'+imgid+'s'+row+'.value');
    var delrequest = new XMLHttpRequest();
    delrequest.open("POST", "imgremove.php");
    delrequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    delrequest.send("filename=" + name);

    var parent = elt.parentNode.parentNode.parentNode;
    elt.parentNode.parentNode.parentNode.removeChild(elt.parentNode.parentNode);
	elt = parent; /* prepare for reg_change */
	var imgidx = imgid.substring(1);
	uploadingImages.delete(imgidx);
	var imgfld = eval("Mainform.image_set"+row);
	imgfld.value = imgfld.value.replace(","+imgid+",", ",");
  }	 
  else if(status == "uploading")
  { var parent = elt.parentNode.parentNode.parentNode;
    elt.parentNode.parentNode.parentNode.removeChild(elt.parentNode.parentNode);
	elt = parent; /* prepare for reg_change */
	var imgidx = imgid.substring(1); /* skip the leading 'n' for new images */
	uploadingImages.delete(imgidx);
	request_array[imgidx].abort();
	var imgfld = eval("Mainform.image_set"+row);
	imgfld.value = imgfld.value.replace(","+imgid+",", ",");
  }
  if((status == "update") || (status == "uploading") || (status == "insert"))
  { var image_dft_root = eval("Mainform.image_default"+row);
    if(image_dft_root.value == imgid)
	{ image_dft_root.value = -1;
	  if(status == "update")
	  { var dftimg = document.getElementById("idefimg"+imgid);
	    dftimg.src = "imgnotdefault.png";
	  }
    }
  }
  recalculate_imgdefault(row);
  reg_change(elt);
}

function set_image_default(row,elt)
{ var img = elt.childNodes[0].src;
  var filename = img.split("/").pop();
  if(filename == "imgdefault.png") return;
  var imgid = elt.parentNode.parentNode.id.substring(4);
  var fld = eval('Mainform.image_default'+row);
  var olddefault = fld.value;
  if(olddefault != 0)
  { var oldimg = document.getElementById("idefimg"+olddefault);
    oldimg.src = "imgnotdefault.png";
  }
  elt.childNodes[0].src = "imgdefault.png";
  fld.value = imgid;
  reg_change(elt);
}

function add_image(elt,evnt,row)
{ /* imgcollector is a hidden input type=file directly under the td */ 
  /* it is used to enable drag-and-drop */ 
  var imgcollector = elt.parentNode.parentNode.parentNode.querySelector(".imgcollector");
  imgcollector.click();
  evnt.stopPropagation();
}

function imgcollector_change(elt, row)
{ if(!elt.files) return;
  var filelist = Array.from(elt.files);
  for(var i=0; i<filelist.length; i++)
	  upload_image_file(filelist[i],row);
}

function del_feature(elt)
{ var tr = elt.parentNode.parentNode;
  tr.innerHTML = "";
}

function add_feature(row,fieldname,mode)
{ event.preventDefault();
  var countfield = document.getElementsByName(fieldname+'_count'+row)[0];
  var count = parseInt(countfield.value);
  var target = document.getElementById(fieldname+'content'+row);
  
  var insertx = "<tr><td>";
  if(mode == 1) /* select */
  { var fld = document.getElementById(fieldname+'_sel'+row);
	if(fld.selectedIndex == 0) return;
    for(var t=0; t<count; t++)
	{ var testval = eval("Mainform."+fieldname+t+'s'+row);
      if(!testval) continue; /* deal with deleted features */
	  if(testval.value == fld.value) return; /* do nothing when the select is already there */
	}
	var value = '<b>'+fld.options[fld.selectedIndex].text+'</b>';
	insertx += '<input type=hidden name='+fieldname+count+'s'+row+' value="'+fld.value+'">'+value;
  }
  else if((mode==2) || (mode == 3))
  { var fld = document.getElementById(fieldname+'_input'+row);
    var value = fld.value.replace("/<[^>]*>/","").replace("/[<>;=#{}]/","");
	fld.value = value;
	if(value == "")
    { alert("empty input field "+row);
	  return;
	}
	if(mode==2)
	  insertx += '<input name='+fieldname+count+'t'+row+' value="'+value+'">';
	else /* mode=3 */
	  insertx += '<textarea name='+fieldname+count+'t'+row+' rows="2" cols="20">'+value+'</textarea>';
	insertx += '<input type=hidden name='+fieldname+count+'s'+row+' value="0">';
  }
  else // mode==4: add empty input field 
  { insertx += '<input name='+fieldname+count+'t'+row+'>';
	insertx += '<input type=hidden name='+fieldname+count+'s'+row+' value="0">';
  }
  var newrow = target.tBodies[0].insertRow();
  newrow.outerHTML = insertx+'</td><td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"/></a></td></tr>';
  countfield.value=1+count;
}

function virtual_change(elt)
{ reg_change(elt);
  var val = elt.checked;
  var setting = "none";
  if(val == 1)
	  setting = "table-cell";
  var tbl = elt.parentNode.parentNode.parentNode;
  
  for(var i=1; i<7; i++)
  { tbl.rows[0].cells[i].style.display = setting;
	tbl.rows[1].cells[i].style.display = setting;
  }	
}

function virtual_delete(elt)
{ reg_change(elt);
  var rownum = elt.name.substring(9);
  var myspan = document.getElementById("dl_span"+rownum);
  if(elt.checked)
	  myspan.style.display = "inline";
  else
	  myspan.style.display = "none";	  
}

function image_delete(elt)
{ reg_change(elt);
	
}

function upload_change(elt)
{ reg_change(elt);
  var rownum = elt.name.substring(9);
  var setting = "none";
  if(elt.value != "")
	  setting = "table-cell";
  var tbl = elt.parentNode.parentNode.parentNode.parentNode;
  
  for(var i=2; i<7; i++)
  { tbl.rows[0].cells[i].style.display = setting;
	tbl.rows[1].cells[i].style.display = setting;
  }
  if(elt.value != "") /* if there is a file */
  { var fld = eval('Mainform.dl_display_filename'+rownum);
    fld.value = elt.value.replace(/^.*[\\\/]/, '');
    var fld = eval('Mainform.date_expiration'+rownum);
    if(fld.value == "")
	{ fld.value = "0000-00-00";
      var fld = document.getElementById('dl_active'+rownum); /* not that we have here two fields with the same fieldname thanks to checkbox shading */
      fld.checked = true;  /* if not initialized we start with true */
	}
    var fld = eval('Mainform.nb_days_accessible'+rownum);
    if(fld.value == "") fld.value = "0";
    var fld = eval('Mainform.nb_downloadable'+rownum);
    if(fld.value == "") fld.value = "0";
  }
}

function name_change(rownum)
{ var link = eval('Mainform.link_rewrite'+rownum);
  if(link && force_friendly_product) /* if field is editable */
  { var name = eval('Mainform.name'+rownum);
    var nameval = name.value.replace(/<[^>]*>/g,'');
	link.value = str2url(nameval);
  }
}

/* the grow() functions handle the -W+ and -H+ buttons that appear in the hide-show-edit fields in edit mode */
/* mode is the "val" field that indicates which of the three edit option we chose */
function grow_image(mode, fieldno, height, width)
{ var theight, twidth;
  var tblEl = document.getElementById("offTblBdy");
  //var rows = -1, cols;
  //var imagecol = getColumn("image");
  for(var i=0; i<tblEl.rows.length; i++)
  { if(!tblEl.rows[i].cells[fieldno]) continue;
//    var images = tblEl.rows[i].cells[fieldno].getElementsByTagName("img");
	var textareas = tblEl.rows[i].cells[fieldno].getElementsByTagName("textarea");
	for(j=0; j<textareas.length; j++)
	{ theight = textareas[j].offsetHeight;
	  theight = theight+height-6;  /* the borders are 6 thick and must be subtracted */
	  if(theight < 14) theight = 14;
	  textareas[j].style.height = theight+"px";
	  
	  twidth = textareas[j].offsetWidth;
	  twidth = twidth+width-6;
	  if(twidth < 50) twidth = 50;
	  textareas[j].style.width = twidth+"px";
	}
  }
}

function grow_input(field, fieldno, width)
{ var tblEl = document.getElementById("offTblBdy");
  var size = -1;
  for(var i=0; i<tblEl.rows.length; i++)
  { if(!tblEl.rows[i].cells[fieldno]) continue; 
	row = tblEl.rows[i].cells[0].childNodes[1].name.substring(10); /* "id_product" is 10 chars long; so this gives row number */
	if(field != "discount")
	{ myfield = eval("Mainform."+field+row);
      if(size == -1)  /* this way we need to read only onc */
	  { size = myfield.size;
	    size += width;
	    if(size < 10) size = 10;
	  }
	  myfield.size = size;
	}
	else /* discount */
	{ var flds = tblEl.rows[i].cells[fieldno].getElementsByTagName("input");
	  for(var j=0; j<flds.length; j++)
	  { if(flds[j].getAttribute("type") == "hidden")
		  continue;
	    fldwidth = flds[j].style.width;
		fldwidth = fldwidth.substring(0,fldwidth.length-2);
		
		if(width > 0)
	      fldwidth = parseInt(fldwidth)+10;
		else 
	      fldwidth = parseInt(fldwidth)-10;
	    if(fldwidth < 10) fldwidth = 10;
	    flds[j].style.width = fldwidth+"px";
	  }
	}
  }
}

function grow_feature(field, fieldno, width)
{ var tblEl = document.getElementById("offTblBdy");
  var size = -1;
  for(var i=0; i<tblEl.rows.length; i++)
  { if(!tblEl.rows[i].cells[fieldno]) continue; 
	row = tblEl.rows[i].cells[0].childNodes[1].name.substring(10);  /* id_product is 10 chars long */
	myfield = eval("Mainform."+field+row);
    if(size == -1)
	{ size = myfield.size;
	  size += width;
	  if(size < 10) size = 10;
	}
	myfield.size = size;
  }
}

function grow_textarea(field, fieldno, height, width)
{ var tblEl = document.getElementById("offTblBdy");
  var rows = -1, cols;
  for(var i=0; i<tblEl.rows.length; i++)
  { if(!tblEl.rows[i].cells[fieldno]) continue; 
    if(field == 'customizations')
    { let textareas = tblEl.rows[i].cells[fieldno].getElementsByTagName("textarea");
	  let len = textareas.length;
	  for(var j=0; j<len; j++)
	  { var myfield = textareas[j];
	    if(rows == -1)
	    { rows = myfield.rows;
	      cols = myfield.cols;
	      rows += height;
	      cols += width;
	      if(cols < 10) cols = 10;
	      if(rows < 1) rows = 1;	  
	    }
	    myfield.cols = cols;
	    myfield.rows = rows;
	  }
	}
    else
    { 
	  row = tblEl.rows[i].cells[0].childNodes[1].name.substring(10);  /* id_product is 10 chars long */
	  myfield = eval("Mainform."+field+row);
      if(rows == -1)
	  { rows = myfield.rows;
	    cols = myfield.cols;
	    rows += height;
	    cols += width;
	    if(cols < 10) cols = 10;
	    if(rows < 2) rows = 2;	  
	  }
	  myfield.cols = cols;
	  myfield.rows = rows;	
	  if(width != 0)
	  { var mychild = myfield.parentNode.childNodes[0];
	    if(mychild.id.substring(0,4) == 'mceu') /* if tinymce */
	    { if(mychild.style.width != "100%")
		  { let mywidth = parseInt(mychild.style.width.replace('px',''));
			if(width > 0)
				mywidth += 50;
			else
				mywidth -= 50;
		    if(mywidth > 500)
			  mychild.style.width = mywidth+"px";
		  }
		}
	  }
	}
	
  }
}

function balert(txt)
{ var warning = document.getElementById("warning");
  warning.innerHTML = warning.innerHTML+txt.replace('www.Prestools.com','<a href="http://www.prestools.com/">www.Prestools.com</a>');
  alert(txt);
}

function add_discount(row)
{ var count_root = eval('Mainform.discount_count'+row);
  var dcount = parseInt(count_root.value);
/* function fill_discount(row,entry,id,status, shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,to,newpricex,newpricei)             */
  var blob = fill_discount(row,dcount,"","new","",	"",			"",		"0",	"0",	"0",	"",		"1",	"",			"1",		"",			"","",   0,         0);
  var new_div = document.createElement('div');
  new_div.innerHTML = blob;
  var adder = document.getElementById("discount_adder"+row);
  adder.parentNode.insertBefore(new_div,adder);
  count_root.value = dcount+1;
  return false;
}

/* clicking on the pencil calls this function to create the dhtml window: copy all the fields from the main window */
function edit_discount(row, entry)
{ var changed = 0;
  var status = eval('Mainform.discount_status'+entry+'s'+row+'.value');
  var shop = eval('Mainform.discount_shop'+entry+'s'+row+'.value');
  var currency = eval('Mainform.discount_currency'+entry+'s'+row+'.value');
  var group = eval('Mainform.discount_group'+entry+'s'+row+'.value');
  var country = eval('Mainform.discount_country'+entry+'s'+row+'.value');
  
  var blob = '<form name="dhform"><input type=hidden name=row value="'+row+'"><input type=hidden name=entry value="'+entry+'">';
  	blob += '<input type=hidden name="discount_status" value="'+status+'">';	
  	blob += '<input type=hidden name="discount_id" value="'+eval('Mainform.discount_id'+entry+'s'+row+'.value')+'">';			
	blob += '<table id="discount_table" cellpadding="2">';
	blob += '<tr><td><b>Shop id</b></td>';
	if(status == "update")
	{	blob += '<td><input type=hidden name="discount_shop" value="'+eval('Mainform.discount_shop'+entry+'s'+row+'.value')+'">';
		if(shop == "") blob += 'all</td></tr>';
		else blob+=''+shop+'</td></tr>';
		blob += '<tr><td><b>Attribute</b></td><td><input type=hidden name="discount_attribute" value="'+eval('Mainform.discount_attribute'+entry+'s'+row+'.value')+'">';
	}
	else /* insert */
	{	blob += '<td><select name="discount_shop" onchange="changed = 1;">';
		blob += '<option value="0">All</option>'+(((shop == "") || (shop == 0))? shopblock : shopblock.replace(">"+shop+"-", " selected>"+shop+"-"))+'</select></td></tr>';
		blob += '<tr><td><b>Attribute</b></td><td><input name="discount_attribute" value="'+eval('Mainform.discount_attribute'+entry+'s'+row+'.value')+'" onchange="changed = 1;"></td></tr>';
	}
	
	blob += '<tr><td><b>Currency</b></td>';
	blob += '<td><select name="discount_currency" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((currency == "")? currencyblock : currencyblock.replace(">"+currency+"<", " selected>"+currency+"<"))+'</select></td></tr>';

	blob += '<tr><td><b>Country</b></td>';
	blob += '<td><select name="discount_country" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((country == "")? countryblock : countryblock.replace(">"+country+"-", " selected>"+country+"-"))+'</select></td></tr>';
	
	blob += '<tr><td><b>Group</b></td>';
	blob += '<td><select name="discount_group" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((group == "")? groupblock : groupblock.replace(">"+group+"-", " selected>"+group+"-"))+'</select></td></tr>';

	blob += '<tr><td><b>Customer id</b></td><td><input name="discount_customer" value="'+eval('Mainform.discount_customer'+entry+'s'+row+'.value')+'" onchange="changed = 1;"> &nbsp; 0=all customers</td></tr>';
	
	blob += '<tr><td><b>Price</b></td><td><input name="discount_price" value="'+eval('Mainform.discount_price'+entry+'s'+row+'.value')+'" onchange="changed = 1; discount_change(this,0,0);" style="width:70px"> &nbsp; From price ex Vat. Leave empty when equal to normal price.</td></tr>';
	blob += '<tr><td><b>Quantity</b></td><td><input name="discount_quantity" value="'+eval('Mainform.discount_quantity'+entry+'s'+row+'.value')+'" onchange="changed = 1;"> &nbsp; Threshold for reduction.</td></tr>';
	blob += '<tr><td><b>Reduction</b></td><td><input name="discount_reduction" value="'+eval('Mainform.discount_reduction'+entry+'s'+row+'.value')+'" onchange="changed = 1; discount_change(this,0,0);"></td></tr>';
	var reductiontax = eval('Mainform.discount_reductiontax'+entry+'s'+row);
	blob += '<tr><td><b>Red. tax</b></td><td><select name="discount_reductiontax" onchange="changed = 1; discount_change(this,0,0);">';
	if(prestashop_version >= "1.6.0.11")	/* for PS >= 1.6.0.11 */
	{ if(reductiontax.value == 1)
	     blob += '<option value=0>excl tax</option><option value=1 selected>incl tax</option>';
	  else
	     blob += '<option value=0 selected>excl tax</option><option value=1>incl tax</option>';
	}
	else
	   blob += '<option value=1>incl tax</option>';		
	blob += '</select> &nbsp; only relevant with amounts and PS > 1.6.0.11</td></tr>';	
	blob += '<td><b>Red. type</b></td><td><select name="discount_reductiontype" onchange="changed = 1; discount_change(this,0,0);">';
    if(eval('Mainform.discount_reductiontype'+entry+'s'+row+'.selectedIndex') == 1)
	   blob += '<option>amt</option><option selected>pct</option>';
	else
	   blob += '<option selected>amt</option><option>pct</option>';
	blob += '</select></td></tr>';
	blob += '<tr><td><nobr><b>From date</b></nobr></td><td><input name="discount_from" value="'+eval('Mainform.discount_from'+entry+'s'+row+'.value')+'" class="datum" onchange="changed = 1;"> &nbsp; format: yyyy-mm-dd</td></tr>';
	blob += '<tr><td><b>To date</b></td><td><input name="discount_to" value="'+eval('Mainform.discount_to'+entry+'s'+row+'.value')+'" class="datum" onchange="changed = 1;"> &nbsp; format: yyyy-mm-dd</td></tr>';
    var newpricex_fld = document.getElementById("discount_newprice_excl"+entry+"s"+row);
    var newpricei_fld = document.getElementById("discount_newprice_incl"+entry+"s"+row);
	blob += '<tr><td><b>New Price</b></td><td><input id="discount_newprice_excl" value="'+newpricex_fld.value+'" onchange="discount_change(this,0,0)" style="width:60px;" class="calculated"> Excl';
	blob += ' &nbsp; <input id="discount_newprice_incl" value="'+newpricei_fld.value+'" onchange="discount_change(this,0,0)" style="width:60px;" class="calculated"> Incl VAT - (calculated values)</td></tr>';
	blob += '<tr><td></td><td align="right"><input type=button value="submit" onclick="submit_dh_discount()"></td></tr></table></form>'; 
    googlewin=dhtmlwindow.open("Edit_discount", "inline", blob, "Edit discount for product "+get_product_id(row), "width=580px,height=425px,resize=1,scrolling=1,center=1", "recal");
  return false;
}

function submit_dh_discount()	/* submit dhtml window and enter data in main page */
{ /*					row				entry				id					status					shop			attribute			*/
  var currency = dhform.discount_currency.options[dhform.discount_currency.selectedIndex].text;
  var country = dhform.discount_country.options[dhform.discount_country.selectedIndex].text;
  country = country.substring(0,country.indexOf('-'));
  var group = dhform.discount_group.options[dhform.discount_group.selectedIndex].text;
  group = group.substring(0,group.indexOf('-'));
  var reductiontype = dhform.discount_reductiontype.options[dhform.discount_reductiontype.selectedIndex].text;
  var reductiontax = dhform.discount_reductiontax.options[dhform.discount_reductiontax.selectedIndex].value;
  var newpricex_fld = document.getElementById("discount_newprice_excl");
  var newpricei_fld = document.getElementById("discount_newprice_incl");
  var blob = fill_discount(dhform.row.value,dhform.entry.value,dhform.discount_id.value,dhform.discount_status.value,dhform.discount_shop.value,dhform.discount_attribute.value,currency,country,group,dhform.discount_customer.value,dhform.discount_price.value,dhform.discount_quantity.value,dhform.discount_reduction.value,reductiontax,reductiontype,dhform.discount_from.value,dhform.discount_to.value,newpricex_fld.value,newpricei_fld.value);
  var eltname = 'discount_table'+dhform.entry.value+'s'+dhform.row.value;
  var target = document.getElementById(eltname);
  target = target.parentNode;
  target.innerHTML = blob;
  reg_change(target);
  googlewin.close();
}

function del_discount(row, entry)
{ var tab = document.getElementById("discount_table"+entry+"s"+row);
  tab.innerHTML = "";
  var statusfield = eval('Mainform.discount_status'+entry+'s'+row);
  statusfield.value = "deleted";
  reg_change(tab);
  return false;
}

/* the ps_specific_prices table has two unique keys that forbid that two too similar reductions are inserted.
 * This function - called before submit - checks for them. 
 * Without this check you get errors like: 
 *   Duplicate entry '113-0-0-0-0-0-0-0-15-0000-00-00 00:00:00-0000-00-00 00:00:00' for key 'id_product_2'
 * This key contains the following fields: id_product, id_shop,id_shop_group,id_currency,id_country,id_group,id_customer,id_product_attribute,from_quantity,from,to 
 * Note that this key has changed over different PS versions. So the check here may be too strong for some versions and too weak for others. */
 function check_discounts(rowno)
{ var field = eval("Mainform.discount_count"+rowno);
  if (!field || (field.value == 0))
    return true;
  var keys2 = new Array();
  for(var i=0; i< field.value; i++)
  { if(eval("Mainform.discount_status"+i+"s"+rowno+".value") == "deleted")
      continue;
    var key = eval("Mainform.id_product"+rowno+".value")+"-"+eval("Mainform.discount_shop"+i+"s"+rowno+".value")+"-0-"+eval("Mainform.discount_currency"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_country"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_group"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_customer"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_attribute"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_quantity"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_from"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_to"+i+"s"+rowno+".value");
    for(var j = 0; j < keys2.length; j++) {
        if(keys2[j] == key) 
		{ var tbl= document.getElementById("offTblBdy");
		  var productno = tbl.rows[rowno].cells[1].childNodes[0].text;
		  alert("You have two or more price rules for a product that are too similar for product "+productno+" on row "+rowno+"! Please correct this!");
		  return false;
		}
    }
	keys2[j] = key;
  }
  return true;
}

/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
function fill_discount(row,entry,id,status, shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,to,newpricex,newpricei)
{ 	var blob = '<input type=hidden name="discount_id'+entry+'s'+row+'" value="'+id+'">';
	blob += '<input type=hidden name="discount_status'+entry+'s'+row+'" value="'+status+'">';		
	blob += '<table id="discount_table'+entry+'s'+row+'" class="discount_table"><tr><td rowspan=3><a href="#" onclick="return edit_discount('+row+','+entry+')"><img src="pen.png"></a></td>';
	
	if(customer == "") customer = 0;
	if(country == "") country = 0;
	if(group == "") group = 0;
	if(attribute == "") attribute = 0;
	if(quantity == "") quantity = 1;
	if(shop == "") shop = 0;
	
	if(status == "update")
	{	blob += '<td class="nobr"><input type=hidden name="discount_shop'+entry+'s'+row+'" value="'+shop+'">';
		if(shop == "0") blob += "all";
		else blob+=shop;
		blob += '-<input type=hidden name="discount_attribute'+entry+'s'+row+'" value="'+attribute+'">';
		if(attribute == "0") blob += "all";
		else blob+=attribute;
	}
	else /* insert */
	{	blob += '<td class="nobr"><input name="discount_shop'+entry+'s'+row+'" style="width:25px" value="'+shop+'" title="shop id" onchange="reg_change(this);"> &nbsp;';
		blob += '<input name="discount_attribute'+entry+'s'+row+'" style="width:25px" value="'+attribute+'" title="product_attribute id" onchange="reg_change(this);"> &nbsp;';
	}
	
	blob += '<select name="discount_currency'+entry+'s'+row+'" value="'+currency+'" title="currency" onchange="reg_change(this);">';
	blob += '<option value="0">All</option>'+((currency == "")? currencyblock : currencyblock.replace(">"+currency+"<", " selected>"+currency+"<"))+'</select> &nbsp;';

	blob += '<input name="discount_country'+entry+'s'+row+'" style="width:25px" value="'+country+'" title="country id" onchange="reg_change(this);"> &nbsp;';
	blob += '<input name="discount_group'+entry+'s'+row+'" style="width:20px" value="'+group+'" title="group id" onchange="reg_change(this);"> &nbsp;';
	blob += '<input style="width:45px" name="discount_customer'+entry+'s'+row+'" value="'+customer+'" title="customer id" onchange="reg_change(this);"></td>';
	
	blob += '<td rowspan=3><a href="#" onclick="return del_discount('+row+','+entry+')"><img src="del.png"></a></td></tr><tr>';


	blob += '<td class="nobr"><input name="discount_price'+entry+'s'+row+'" style="width:50px" value="'+price+'" title="From Price Excl" onchange="reg_change(this); discount_change(this,'+row+','+entry+')"> &nbsp; ';
	blob += '<input name="discount_quantity'+entry+'s'+row+'" style="width:30px" value="'+quantity+'" title="From Quantity" onchange="reg_change(this);"> &nbsp;';
	blob += '<input name="discount_reduction'+entry+'s'+row+'" style="width:40px" value="'+reduction+'" title="Reduction" onchange="reg_change(this); discount_change(this,'+row+','+entry+')"> &nbsp;';
	blob += '<select name="discount_reductiontax'+entry+'s'+row+'" title="Reduction Tax status" onchange="reg_change(this); discount_change(this,'+row+','+entry+')">';
	if(prestashop_version >= "1.6.0.11")	/* for PS >= 1.6.0.11 */
	{ if(reductiontax == "1")
	    blob += '<option value=0>Excl</option><option value=1 selected>Incl</option>';
	  else
	    blob += '<option value=0 selected>Excl</option><option value=1>Incl</option>';
	}
	else
	    blob += '<option value=1>Incl</option>';	  
	blob += '</select> ';	
	
	blob += '<select name="discount_reductiontype'+entry+'s'+row+'" title="Reduction Type" onchange="reg_change(this); discount_change(this,'+row+','+entry+')">';
	if(reductiontype == "pct")
	   blob += '<option>amt</option><option selected>pct</option>';
	else
	   blob += '<option selected>amt</option><option>pct</option>';
	blob += '</select></td></tr><tr><td>';
	blob += ' <input name="discount_from'+entry+'s'+row+'" style="width:70px" value="'+from+'" title="From Date" class="datum" onchange="reg_change(this);">';
	blob += ' <input name="discount_to'+entry+'s'+row+'" style="width:70px" value="'+to+'" title="To Date" class="datum" onchange="reg_change(this);">';

	blob += ' <input id="discount_newprice_excl'+entry+'s'+row+'" style="width:40px" value="'+newpricex+'" onchange="discount_change(this,'+row+','+entry+')" title="calculated price excl VAT" class="calculated">';
    blob += ' <input id="discount_newprice_incl'+entry+'s'+row+'" style="width:40px" value="'+newpricei+'" onchange="discount_change(this,'+row+','+entry+')" title="calculated price incl VAT" class="calculated">';
	blob += "</td></tr></table><hr/>";
	return blob;
}

/* when you add a discount block you cannot immediately execute javascript on it. For that reason the discount_change
 * function that generates the calculated resulting prices for them is executed with a delay. 
 * All the discount blocks that need such an calculation are collected in an array (discount_delayed) that is then 
 * processed by this function. As discount_change() needs to know which field's change is guiding we provide a 
 * "target". For the "add" function this is always "reduction". For the "add fixed target discount" function 
 * it is either "newprice_excl" or "newprice_incl". */
function delayed_discount_change(target)
{ var len = discount_delayed.length;
  for(var i=0; i<len; i++)
  { var elta = eval("Mainform.discount_"+target+discount_delayed[i][1]+"s"+discount_delayed[i][0]);
	if(!elta) alert("Delayed discount target not found "+target);
    discount_change(elta, discount_delayed[i][0],discount_delayed[i][1]);
  }
}

/* discount_change is called when one of the fields is changed. It calculates the new discounted price */
function discount_change(elt,row,entry)
{ var myform = elt.form.name;
  var suffix = "";
  if(myform == "Mainform")
	  suffix = entry+"s"+row;
  var tblEl = document.getElementById("offTblBdy");
  var baseprice = eval(myform+".discount_price"+suffix+".value");
  if(!baseprice)
  { var pricecol = getColumn("price");
    if(pricecol != -1)
        var baseprice = parseFloat(tblEl.rows[row].cells[pricecol].innerHTML);
  }
  else
	  baseprice = parseFloat(baseprice);
  var VATcol = getColumn("VAT");
  if(VATcol != -1) 
    var VAT = parseFloat(tblEl.rows[row].cells[VATcol].innerHTML);
  else 
	var VAT = 0;
  var reductionfield = eval(myform+".discount_reduction"+suffix);
  if(reductionfield.value=="") reductionfield.value="0";
  var reduction = parseFloat(reductionfield.value);
  var reductiontype = eval(myform+".discount_reductiontype"+suffix+".value");
  var reductiontax = parseFloat(eval(myform+".discount_reductiontax"+suffix+".value"));
  
  if(elt.id.substring(0,17) == "discount_newprice") /* if the newprice was changed: change the reduction */
  { if(elt.id.substring(0,22) == "discount_newprice_incl")
	{ var newpricei = parseFloat(eval(myform+".discount_newprice_incl"+suffix+".value"));
	  var newpricex = newpricei * (100/(VAT+100));
	}
	else
	{ var newpricex = parseFloat(eval(myform+".discount_newprice_excl"+suffix+".value"));
	}
	if(reductiontype == "pct")
	  var reduction = Math.round((baseprice - newpricex) / (baseprice * 100));
    else
    { var reduction = baseprice - newpricex;
      if(reductiontax)
		reduction = (reduction * ((100+VAT)/100)).toFixed(2);
    }
	reductionfield.value = reduction;
  }
  if(reductiontype == "pct")
	var newpricex = baseprice * (1 - (reduction/100));
  else
  { if(reductiontax)
		reduction = reduction *(100/(VAT+100));
    var newpricex = baseprice - reduction;
  }
  var newpricei = newpricex * (1 + VAT/100);
  var newpricex_fld = document.getElementById("discount_newprice_excl"+suffix);
  newpricex_fld.value = newpricex.toFixed(2);
  var newpricei_fld = document.getElementById("discount_newprice_incl"+suffix);
  newpricei_fld.value = newpricei.toFixed(2);
}

function selectmode()
{ var blob;
  if(Mainform.selectmodestatus.value == 0)
  {	blob = '<form name="dhform">';
    blob += '<table id="id_selectmode"><tr><td><h1>Select your row select mode</h1>';
	blob += 'You are now in <b>clickaway mode</b> where you can click rows away that you don\'t want to submit. ';
	blob += 'You can switch here to <b>checkbox mode</b> with checkboxes in front of rows and only those selected processed by mass update and submit.</td></tr>';
	blob += '<tr><td align="center"><p/><input type=button value="Switch to Checkbox Mode" onclick="submit_dh_selectmode(\'1\')"></td></tr></table></form>'; 
  }
  else
  {	blob = '<form name="dhform">';
    blob += '<table id="id_selectmode"><tr><td><h1>You are now in checkbox mode for rows</h1>';
	blob += ' - You can select a range of checkboxes when you keep the shiftkey pressed during the second click. The position of the first click will determine whether the range will be set or not.';
	blob += ' - Only rows with selected checkboxes will be processed in Mass edit and Submit All';
	blob += ' - Submit row will always be processed</td></tr>';
	blob += '<tr><td align="center"><p/><input type=button value="Select all rows" onclick="submit_dh_selectmode(\'2\')"></td></tr>'; 
	blob += '<tr><td align="center"><p/><input type=button value="Unselect all rows" onclick="submit_dh_selectmode(\'3\')"></td></tr>'; 
	blob += '<tr><td align="center"><p/><input type=button value="Reverse selection" onclick="submit_dh_selectmode(\'4\')"></td></tr>'; 
	blob += '<tr><td align="center"><p/><input type=button value="Empty not selected rows" onclick="submit_dh_selectmode(\'5\')"></td></tr>'; 
	blob += '<tr><td align="center"><p/><input type=button value="Back to Clickaway Mode" onclick="submit_dh_selectmode(\'0\')"></td></tr></table></form>'; 
  }
  googlewin=dhtmlwindow.open("Choose_selectmode", "inline", blob, "Choose row select mode", "width=580px,height=425px,resize=1,scrolling=1,center=1", "recal");
  return false;
}

function submit_dh_selectmode(mode)
{ var tbl = document.getElementById("offTblBdy");
  var len = tbl.rows.length;
  
  if(mode == 0)  /* back to clickaway mode */
  { Mainform.selectmodestatus.value = 0;
	for(var i=0; i<len; i++)
	{ if(tbl.rows[i].innerHTML == "<td></td>") {continue; }
	  var mycell = tbl.rows[i].cells[0];
	  var id = mycell.id.substring(4); /* id=trid0, trid1, etc */
	  var input = document.createElement("input");
	  input.type = "button";
	  input.value = "X";
	  input.style = "width:4px";
	  input.title="Hide row 0 from display";
	  input.tabIndex = -1;
	  mycell.replaceChild(input, mycell.childNodes[0]);
	  mycell.childNodes[0].onclick=function (id) {   /* an enclosure here */
		return function() {RemoveRow(id);};
	  }(id);
	}
  }
  else if(mode == 1)   /* to checkbox mode */
  { Mainform.selectmodestatus.value = 1;
	for(var i=0; i<len; i++)
	{ if(tbl.rows[i].innerHTML == "<td></td>") {continue; }
	  var mycell = tbl.rows[i].cells[0];
	  var id = mycell.id.substring(4); /* id=trid0, trid1, etc */
	  var divje = document.createElement("div");
	  divje.innerHTML = '<input type="checkbox" name="sel'+id+'" onclick="checker(this,event);" checked>';
	  mycell.replaceChild(divje.childNodes[0], mycell.childNodes[0]);
	  
/*	  var input = document.createElement("input");
	  input.type = "checkbox";
	  input.name = "sel"+id;
	  input.checked = "checked";
	  mycell.replaceChild(input, mycell.childNodes[0]);
*/	}
  }
  else if(mode == 2)  /* check all checkboxes */
  { for(var i=0; i<len; i++)
	{ if(tbl.rows[i].innerHTML == "<td></td>") {continue; }
	  var mycell = tbl.rows[i].cells[0];
	  var id = mycell.id.substring(4); /* id=trid0, trid1, etc */
	  var fld = eval("Mainform.sel"+id);
	  fld.checked = true;
	}
  }
  else if(mode == 3)   /* uncheck all checkboxes */
  { for(var i=0; i<len; i++)
	{ if(tbl.rows[i].innerHTML == "<td></td>") {continue; }
	  var mycell = tbl.rows[i].cells[0];
	  var id = mycell.id.substring(4); /* id=trid0, trid1, etc */
	  var fld = eval("Mainform.sel"+id);
	  fld.checked = false;
	}
  }
  else if(mode == 4)    /* invert all checkboxes */
  { for(var i=0; i<len; i++)
	{ if(tbl.rows[i].innerHTML == "<td></td>") {continue; }
	  var mycell = tbl.rows[i].cells[0];
	  var id = mycell.id.substring(4); /* id=trid0, trid1, etc */
	  var fld = eval("Mainform.sel"+id);
	  if(fld.checked) 
		 fld.checked = false; 
	  else
	     fld.checked = true;
	}
  }
  else if(mode == 5)    /* empty unchecked rows */
  { for(var i=0; i<len; i++)
	{ if(tbl.rows[i].innerHTML == "<td></td>") {continue; }
	  var mycell = tbl.rows[i].cells[0];
	  var id = mycell.id.substring(4); /* id=trid0, trid1, etc */
	  var fld = eval("Mainform.sel"+id);
	  if(!fld.checked) 
	  { tbl.rows[i].innerHTML = "<td></td>";
	  }
	}
  }
  googlewin.close();
}

/* checker allows you to select ranges of checkboxes */
var lastclickindex=-1;
var lastclickpos =0;
function checker(elt,evnt)
{ var first, last;
  var tbl = document.getElementById("offTblBdy");
  var len = tbl.rows.length;
  for(var i=0; i<len; i++)  /* the table may have been sorted so the id's may not be ordered */
  { if(tbl.rows[i].childNodes[0].id == elt.parentNode.id)
	{ var clickindex=i;
	  break;
	}
  }
  if (evnt.shiftKey) alert("pop");
  if ((evnt.shiftKey) && (lastclickindex != -1))
  { if(lastclickindex < clickindex) 
    { first=lastclickindex; last=clickindex; }
    else
	{ last=lastclickindex; first=clickindex; }
    for(i=first; i<=last; i++)
	{ tbl.rows[i].cells[0].firstChild.checked= lastclickpos;
	}
  }
  else
  { lastclickindex = clickindex;
	lastclickpos = elt.checked;
  }
}

function useTinyMCE(elt, field)
{ if (typeof tinymce == 'undefined')
  { alert("TinyMCE could not be loaded! Please check your internet connection!"); return false; }
  while (elt.nodeName != "TD")
  {  elt = elt.parentNode;
  }
  elt.childNodes[0].cols="125";
  elt.childNodes[1].style.display = "none";  /* hide the links */
  tinymce.init({
//	content_css: "http://localhost/css/my_tiny_styles.css",
//    fontsize_formats: "8pt 9pt 10pt 11pt 12pt 26pt 36pt",	
	selector: "#"+field, 
	setup: function (ed) {
 	  ed.on("change", function (e) {
           console.log('the event object ', e);
           console.log('the editor object ', ed);
           console.log('the content ', ed.getContent());
		tinyChange(e,ed);
	    })
	}
//	width:500
//	setup: function (ed) {
//  	ed.on("change", function (e) {
//        })
//	}
  });		// Note: onchange_callback was for TinyMCE 3.x and doesn't work in 4.x
}

function tinyChange(e,ed)
{ var tmp = eval("Mainform."+ed.id);
  reg_change(tmp);
}

/* the arguments for this version were derived from source code of the "classic" example on the TinyMCE website */
/* some buttons were removed but all plugins were maintained */
function useTinyMCE2(elt, field)
{ if (typeof tinymce == 'undefined')
  { alert("TinyMCE could not be loaded! Please check your internet connection!"); return false; }
  while (elt.nodeName != "TD")
  {  elt = elt.parentNode;
  }
  elt.childNodes[0].cols="160";
  elt.childNodes[1].style.display = "none";  /* hide the use TinyMce links */
  tinymce.init({
  	selector: "#"+field, 
	plugins: [
		"colorpicker advlist autolink autosave link image lists charmap print filemanager preview hr pagebreak spellchecker",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking anchor",
		"table contextmenu directionality emoticons template textcolor paste fullpage textcolor textpattern"
	],
	toolbar1: "bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview",
	toolbar3: "forecolor backcolor | table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | spellchecker | visualchars visualblocks nonbreaking",
	menubar: false,
	toolbar_items_size: 'small',
	style_formats: [
		{title: 'Bold text', inline: 'b'},
		{title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
		{title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
		{title: 'Example 1', inline: 'span', classes: 'example1'},
		{title: 'Example 2', inline: 'span', classes: 'example2'},
		{title: 'Table styles'},
		{title: 'Table row 1', selector: 'tr', classes: 'tablerow1'}
	],
	width: 660,
	autosave_ask_before_unload: false,
    relative_urls: false,
    image_advtab: true,
	setup: function (ed) {
 	  ed.on("change", function (e) {
		tinyChange(e,ed);
	    })
	}
  });
}  


function Addcarrier(plIndex)
{ var list = document.getElementById('carrierlist'+plIndex); /* available carriers */
  var sel = document.getElementById('carriersel'+plIndex);	/* selected carriers */
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  var carrier = list.options[listindex].text;
  var car_id = list.options[listindex].value;
  list.options[listindex]=null;		/* remove from available carriers list */
  if(sel.options[0].value == "none")
  { sel.options.length = 0;
    max = 0;
  }
  i=0;
  var base = sel.options;
  while((i<max) && (carrier > base[i].text)) i++;
  if(i==max)
    base[max] = new Option(carrier);
  else
  { let newOption = new Option(carrier);
    if (document.createElement && (newOption = document.createElement('option'))) 
    { newOption.appendChild(document.createTextNode(carrier));
	}
    sel.insertBefore(newOption, base[i]);
  }
  base[i].value = car_id;
  var mycars = eval("document.Mainform.mycars"+plIndex);
  mycars.value = mycars.value+','+car_id;
}

function Removecarrier(plIndex)
{ var list = document.getElementById('carrierlist'+plIndex);
  var sel = document.getElementById('carriersel'+plIndex);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, max = list.options.length;
  var carrier = sel.options[selindex].text;
  if(carrier == "none") return; /* none selected */
  var car_id = sel.options[selindex].value;
  var classname = sel.options[selindex].className;
  sel.options[selindex]=null;
  i=0;
  while((i<max) && (carrier > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(carrier);
  else
  { let newOption = new Option(carrier);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(carrier));
    list.insertBefore(newOption, list.options[i]);
  }
  if(sel.options.length == 0)
    sel.options[0] = new Option("none");
  list.options[i].value = car_id;
  var mycars = eval("document.Mainform.mycars"+plIndex);
  mycars.value = mycars.value.replace(','+car_id, '');
}

function fillCarriers(idx,cars)
{ var list = document.getElementById('carrierlist'+idx);
  for(var i=0; i< cars.length; i++)
  { for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == cars[i])
	  { list.selectedIndex = j;
		Addcarrier(idx);
	  }
	}
  }
}

function Addsupplier(plIndex, init)
{ var list = document.getElementById('supplierlist'+plIndex);
  var sel = document.getElementById('suppliersel'+plIndex);
  var sellen = sel.options.length;
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  var supplier = list.options[listindex].text;
  var sup_id = list.options[listindex].value;
  list.options[listindex]=null;
  i=0;
  var base = sel.options;
  while((i<max) && (supplier > base[i].text)) i++;
  if(i==max)
    base[max] = new Option(supplier);
  else
  { let newOption = new Option(supplier);
    if (document.createElement && (newOption = document.createElement('option'))) 
    { newOption.appendChild(document.createTextNode(supplier));
	}
    sel.insertBefore(newOption, base[i]);
  }
  base[i].value = sup_id;
  if(init == 1) /* if this function is called by the add button (= not from SwitchDisplay) we need to add a line for each attribute with zero values */
  { var attributes = eval("document.Mainform.supplier_attribs"+plIndex+".value"); /* for products without attribute this field will contain "0" */
    var myattribs = attributes.split(",");
    if(sellen == 0) /* if there was no supplier we must make this one default */
    { sel.options[0].className = 'defcat';
	  var default_supplier = eval("document.Mainform.supplier_default"+plIndex);
	  default_supplier.value = sel.options[0].value;
    }
    for(i=0; i < myattribs.length; i++)
    { var tab = document.getElementById("suppliertable"+myattribs[i]+"s"+plIndex);
	  if(tab.rows[0])
		tab.rows[0].deleteCell(4); /* the attribute name is shared. If it is already there we delete it so that we can re-add it with the new line */
      for (j=0; j<= tab.rows.length; j++)
	  { if(!tab.rows[j] || tab.rows[j].cells[0].innerHTML > supplier)
	    { if(sellen == 0) /* if there was no supplier we must make this one default */
			var rowstart = '<td class="defcat">';
		  else
			var rowstart = '<td>';			  
		  var newRow = tab.insertRow(j);
		  newRow.innerHTML=rowstart+supplier+'</td><td><input name="supplier_reference'+myattribs[i]+'t'+sup_id+'s'+plIndex+'" value="" onchange="reg_change(this);" /></td><td><input name="supplier_price'+myattribs[i]+'t'+sup_id+'s'+plIndex+'" value="0.000000" onchange="reg_change(this);" /></td>'+
		  '<td><select name="supplier_currency'+myattribs[i]+'t'+sup_id+'s'+plIndex+'" onchange="reg_change(this);">'+currencyblock+'</select></td>';
		  break;
		}
	  }
//	  tab.rows[0].innerHTML += '<td rowspan="'+tab.rows.length+'">'+tab.title+'</td>'; 
// The previous line emptied the content of new suppliers when the inserted supplier came after that line.
	  var cell = tab.rows[0].insertCell(-1);
	  cell.rowSpan = tab.rows.length;
	  cell.innerHTML = tab.rows.length;
	}
  }  
  var mysups = eval("document.Mainform.mysups"+plIndex);
  mysups.value = mysups.value+','+sup_id;
  return i;
}

function Removesupplier(plIndex)
{ var list = document.getElementById('supplierlist'+plIndex);
  var sel = document.getElementById('suppliersel'+plIndex);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, j, max = list.options.length;
  var supplier = sel.options[selindex].text;
  var sup_id = sel.options[selindex].value;
  var classname = sel.options[selindex].className;
  var sellen = sel.options.length;
  sel.options[selindex]=null;
  if(classname == 'defcat')
  { var default_supplier = eval("document.Mainform.supplier_default"+plIndex);
	if(sellen > 1)
	{ default_supplier.value = sel.options[0].value;
	  sel.options[0].className = 'defcat';
	}
    else
	  default_supplier.value = 0;
  }
  i=0; /* now add it to the list */
  while((i<max) && (supplier > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(supplier);
  else
  { let newOption = new Option(supplier);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(supplier));
    list.insertBefore(newOption, list.options[i]);
  }
  list.options[i].value = sup_id;
  var attributes = eval("document.Mainform.supplier_attribs"+plIndex+".value");
  var myattribs = attributes.split(",");
  for(i=0; i < myattribs.length; i++)
  { var tab = document.getElementById("suppliertable"+myattribs[i]+"s"+plIndex);
    tab.rows[0].deleteCell(4);
    for (j=0; j< tab.rows.length; j++)
	{ if(tab.rows[j].cells[0].innerHTML == supplier)
	  { tab.deleteRow(j);
	  }
	}
	if(tab.rows.length > 0)
	{ tab.rows[0].innerHTML += '<td rowspan="'+tab.rows.length+'">'+tab.title+'</td>';
	  if(classname == 'defcat') /* if deleted row was default supplier, make the first row so */
	    tab.rows[0].cells[0].className = "defcat";
    }
  }
  var mysups = eval("document.Mainform.mysups"+plIndex);
  mysups.value = mysups.value.replace(','+sup_id, '');
}

function get_product_id(row)
{ var prod_base = eval("document.Mainform.id_product"+row);
  if(!prod_base) return 0;
  return prod_base.value;
}

function fillSuppliers(idx,tmp)
{ tmp = tmp.replace(/ /g,''); /* remove the spaces that we added for linebreaks */
  var cats = tmp.split(',<');
  var list = document.getElementById('categorylist'+idx);
  var sel = document.getElementById('categorysel'+idx);
  var defcatvalue = -1;
  for(var i=0; i< cats.length; i++)
  { if(i!=0)
	  cats[i] = "<"+cats[i];
	if(!cats[i].match("text-decoration"))
	  defcatvalue = cats[i]= striptags(cats[i]);
	else 
	  cats[i]= striptags(cats[i]);
    for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == cats[i])
	  { list.selectedIndex = j;
		Addcategory(idx);
	  }
	}
  }
  for(var k=0; k< sel.length; k++)
  { if(sel.options[k].value == defcatvalue)
    { defcat = k; break; 
	}
  }
  if(defcatvalue >= 0)
  { sel.options[defcat].className = 'defcat';
    var default_cat = eval("document.Mainform.category_default"+idx);
    default_cat.value = '0'; // zero indicates that is was not changed
  }
  else
  { alert("No default supplier found for row "+idx+" (product "+get_product_id(idx)+"). First available taken.");
	sel.options[0].className = 'defcat';
	var default_cat = eval("document.Mainform.category_default"+idx);
    default_cat.value = sel.options[0].value;
	reg_change(sel);
//alternative solution: next rows makes changing impossible
// list.parentNode.parentNode.parentNode.parentNode.parentNode.innerHTML = "No change allowed!"; // Remove the whole cell.
//    alert("No default found for row "+idx+" ");
  }
}

function MakeSupplierDefault(idx)
{ var sel = document.getElementById('suppliersel'+idx);
  for(var j=0; j< sel.length; j++)
	sel.options[j].className = '';
  if(sel.selectedIndex < 0) return;
  sel.options[sel.selectedIndex].className = 'defcat';
  var default_supplier = eval("document.Mainform.supplier_default"+idx);
  default_supplier.value = sel.options[sel.selectedIndex].value;
  var supplier_name = sel.options[sel.selectedIndex].text;
  
  var attributes = eval("document.Mainform.supplier_attribs"+idx+".value");
  var myattribs = attributes.split(",");
  for(i=0; i < myattribs.length; i++)
  { var tab = document.getElementById("suppliertable"+myattribs[i]+"s"+idx);
    for (j=0; j< tab.rows.length; j++)
	  tab.rows[j].cells[0].className = 0;
	for (j=0; j< tab.rows.length; j++) 
	{ if(tab.rows[j].cells[0].innerHTML == supplier_name)
	  { tab.rows[j].cells[0].className = 'defcat';
	  }
	}
  }
}

function Addcategory(plIndex)
{ var list = document.getElementById('categorylist'+plIndex);
  var sel = document.getElementById('categorysel'+plIndex);
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  var category = list.options[listindex].text;
  var cat_id = list.options[listindex].value;
  list.options[listindex]=null;
  i=0;
  var base = sel.options;
  while((i<max) && (category > base[i].text)) i++;
  if(i==max)
    base[max] = new Option(category);
  else
  { let newOption = new Option(category);
    if (document.createElement && (newOption = document.createElement('option'))) 
    { newOption.appendChild(document.createTextNode(category));
	}
    sel.insertBefore(newOption, base[i]);
  }
  base[i].value = cat_id;
  var mycats = eval("document.Mainform.mycats"+plIndex);
  mycats.value = mycats.value+','+cat_id;
  
  var callerName = arguments.callee.caller ? arguments.callee.caller.name : "global";
  if((sel.options.length == 1) && (callerName=="onclick"))
  {	var default_cat = eval("document.Mainform.category_default"+plIndex);
    sel.options[0].className = 'defcat';
    default_cat.value = sel.options[0].value;
	reg_change(sel);
  }
}

function Removecategory(plIndex)
{ var list = document.getElementById('categorylist'+plIndex);
  var sel = document.getElementById('categorysel'+plIndex);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, max = list.options.length;
  category = sel.options[selindex].text;
  cat_id = sel.options[selindex].value;
  classname = sel.options[selindex].className;
  if(sel.options.length == 1)
  { alert('There must always be at least one selected category!');
    return; /* leave selection not empty */
  }
  sel.options[selindex]=null;
  i=0;
  while((i<max) && (category > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(category);
  else
  { let newOption = new Option(category);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(category));
    list.insertBefore(newOption, list.options[i]);
  }
  list.options[i].value = cat_id;
  if(classname == 'defcat')
  { sel.options[0].className = 'defcat';
    var default_cat = eval("document.Mainform.category_default"+plIndex);
	default_cat.value = sel.options[0].value;
  }
  var mycats = eval("document.Mainform.mycats"+plIndex);
  mycats.value = mycats.value.replace(','+cat_id, '');
}

function fillCategories(idx,tmp)
{ tmp = tmp.replace(/ /g,''); /* remove the spaces that we added for linebreaks */
  var cats = tmp.split(',<');
  var list = document.getElementById('categorylist'+idx);
  var sel = document.getElementById('categorysel'+idx);
  var defcatvalue = -1;
  for(var i=0; i< cats.length; i++)
  { if(i!=0)
	  cats[i] = "<"+cats[i];
	if(!cats[i].match("text-decoration"))
	  defcatvalue = cats[i]= striptags(cats[i]);
	else 
	  cats[i]= striptags(cats[i]);
    for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == cats[i])
	  { list.selectedIndex = j;
		Addcategory(idx);
	  }
	}
  }
  if(sel.length == 0) /* product assigned to no category: use default */
  { var defaultcat = list.parentNode.parentNode.parentNode.parentNode.parentNode.dataset.dflt;
    var default_cat = eval("document.Mainform.category_default"+idx);
	default_cat.value = defaultcat;
    if(defaultcat > 0)
	{ for(var j=0; j< list.length; j++)
	  { if(list.options[j].value == defaultcat)
	    { list.selectedIndex = j;
		  Addcategory(idx);
		  sel.options[0].className = 'defcat';
		  alert("The product on row "+idx+" was not assigned to any category. It has now been assigned to its default category "+defaultcat);
		  reg_change(sel);
	    }
	  }
	}
	return;
  }	  
  for(var k=0; k< sel.length; k++)
  { if(sel.options[k].value == defcatvalue)
    { defcat = k; break; 
	}
  }
  if(defcatvalue > 0)
  { sel.options[defcat].className = 'defcat';
    var default_cat = eval("document.Mainform.category_default"+idx);
    default_cat.value = defcatvalue; // zero indicates that is was not changed
  }
  else
  { alert("No default category found for row "+idx+" (product "+get_product_id(idx)+"). First available taken.");
	sel.options[0].className = 'defcat';
	var default_cat = eval("document.Mainform.category_default"+idx);
    default_cat.value = sel.options[0].value;
	reg_change(sel);
//alternative solution: next rows makes changing impossible
// list.parentNode.parentNode.parentNode.parentNode.parentNode.innerHTML = "No change allowed!"; // Remove the whole cell.
//    alert("No default found for row "+idx+" ");
  }
}

function striptags(mystr) /* remove html tags from text */
{ var regex = /(<([^>]+)>)/ig;
  return mystr.replace(regex, "");
}

function MakeCategoryDefault(idx)
{ var sel = document.getElementById('categorysel'+idx);
  for(var j=0; j< sel.length; j++)
	sel.options[j].className = '';
  if(sel.selectedIndex < 0) return;
  sel.options[sel.selectedIndex].className = 'defcat';
  var default_cat = eval("document.Mainform.category_default"+idx);
  default_cat.value = sel.options[sel.selectedIndex].value;
}

function change_categories()
{ var tmp = document.getElementById('cat_order'); /* this is the "sort by" "position" option that must be hidden when "all categories" is selected */
  var tmp2 = document.getElementById('category_number');
  if(document.search_form.id_category.selectedIndex ==0)
  {	tmp.style.display = 'none';
	tmp.disabled = true;
	document.search_form.order.selectedIndex = 1;
	tmp2.value = '';
	document.search_form.subcats.checked = false;
  }
  else
  { if (tmp.style.display == 'none') /* if not visible: show and select order option "positions" */
    { tmp.style.display = 'inline';
	  tmp.disabled = false;	
      document.search_form.order.selectedIndex = 0;
	}
	tmp3 = document.search_form.id_category;
	tmp2.value = tmp3.options[tmp3.selectedIndex].value;
  }
}

function change_category_number(num)
{ if(num=='x') /* in the searchbox */
  { var tmp = document.getElementById('category_number');
    var mysel = search_form.id_category;
  }
  else /* selecting in one of the edit rows (num = rownum) */
  { var tmp = document.getElementById('category_number'+num);
	var mysel = eval('Mainform.categorylist'+num);
  } 
  var mysellen = mysel.length;
  var myoptions = mysel.options;
  var val = tmp.value;
  if(isNaN(val))  /* if it is not non-numeric we do a text search among the categories on the value */
  { val = val.toLowerCase();
    if(catselectortype == 3)
	{ let found = false;
	  for(var i=1; i<mysellen; i++)
	    myoptions[i].className = "";
	  for(var i=1; i<mysellen; i++)
	  { let key = /^[- ]*/;		/* should there be a backslash before the "-"? */
	    let txt = myoptions[i].text.replace(key,'').toLowerCase();
	    if (txt.substr(0, val.length) == val)
	    { if(!found)
	      { found = true;
		    mysel.selectedIndex = i;
		  }
		  myoptions[i].className = "selcat";			
	    }
	  }
	}
	else
    { for(var i=1; i<mysellen; i++)
	  { if (myoptions[i].text.substr(0, val.length).toLowerCase() == val)
	    { mysel.selectedIndex = i;
		  break;			
	    }
	  }
	}
  }  
  else
  { var found = false;
    for(var i=1; i<mysellen; i++)
	{ if(myoptions[i].value == val)
	  { mysel.selectedIndex = i;
		found = true;	
	  }
	}
	if(!found)
	  mysel.selectedIndex = 0;
  }
  if(num=='x') /* in the searchbox */
  { var tmp = document.getElementById('cat_order');
    if(mysel.selectedIndex == 0)
    { tmp.style.display = 'none';
	  document.search_form.order.selectedIndex = 1;
	  document.search_form.subcats.checked = false;
    }
    else
    { if (tmp.style.display == 'none')
	  { tmp.style.display = 'inline';
        document.search_form.order.selectedIndex = 0;		  
	  }
	}
  }
}

function change_subcats()
{ if(document.search_form.id_category.selectedIndex ==0) 
	document.search_form.subcats.checked = false;
  var tmp = document.getElementById('cat_order');
  if(document.search_form.subcats.checked)
  { tmp.style.display = 'none';
//	document.search_form.order.selectedIndex = 1;
  }
  else if (tmp.style.display == 'none') /* if not visible: show and select order option "positions" */
  { tmp.style.display = 'inline';
//    document.search_form.order.selectedIndex = 0;
  }
}

function Addattachment(plIndex)
{ var list = document.getElementById('attachmentlist'+plIndex);
  var sel = document.getElementById('attachmentsel'+plIndex);
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  var attachment = list.options[listindex].text;
  var attach_id = list.options[listindex].value;
  list.options[listindex]=null;
  if(sel.options[0].value == "none")
  { sel.options.length = 0;
    max = 0;
  }
  i=0;
  var base = sel.options;
  while((i<max) && (attachment > base[i].text)) i++;
  if(i==max)
    base[max] = new Option(attachment);
  else
  { let newOption = new Option(attachment);
    if (document.createElement && (newOption = document.createElement('option'))) 
    { newOption.appendChild(document.createTextNode(attachment));
	}
    sel.insertBefore(newOption, base[i]);
  }
  base[i].value = attach_id;
  var myattachments = eval("document.Mainform.myattachments"+plIndex);
  myattachments.value = myattachments.value+','+attach_id;
}

function Removeattachment(plIndex)
{ var list = document.getElementById('attachmentlist'+plIndex);
  var sel = document.getElementById('attachmentsel'+plIndex);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, max = list.options.length;
  attachment = sel.options[selindex].text;
  if(attachment == "none") return; /* none selected */
  attach_id = sel.options[selindex].value;
  classname = sel.options[selindex].className;
  sel.options[selindex]=null;
  i=0;
  while((i<max) && (attachment > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(attachment);
  else
  { let newOption = new Option(attachment);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(attachment));
    list.insertBefore(newOption, list.options[i]);
  }
  if(sel.options.length == 0)
    sel.options[0] = new Option("none");
  list.options[i].value = attach_id;
  var myattachments = eval("document.Mainform.myattachments"+plIndex);
  myattachments.value = myattachments.value.replace(','+attach_id, '');
}

function fillAttachments(idx,attas)
{ var list = document.getElementById('attachmentlist'+idx);
  for(var i=0; i< attas.length; i++)
  { for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == attas[i])
	  { list.selectedIndex = j;
		Addattachment(idx);
	  }
	}
  }
}

function ChangeAttrGroup()
{ var mygroup = document.search_form.attrfeat.value;
  var val = document.search_form.attrfeatvalue.value;
  if(mygroup == "0")
  { document.search_form.attrfeatvalue.innerHTML = "No Options";
    return;
  }
  var query = "ajaxdata.php?myids="+val+"&task=getattrfeat&group="+mygroup+"&id_lang="+rowform.id_lang.value;
  LoadPage(query,dynamo2);
}

function LoadPage(url, callback)
{ var request =  new XMLHttpRequest("");
  request.open("GET", url, true); /* delaypage must be a global var; changed from POST to GET */
  request.onreadystatechange = function() 
  { if (request.readyState == 4 && request.status == 404) /* done = 4, ok = 200 */
	alert("ERROR "+request.status+" "+request.responseText);
    if (request.readyState == 4 && request.status == 200) /* done = 4, ok = 200 */
    { if (request.responseText) 
        callback(request.responseText);
    }
  };
  request.send(null);
}

function dynamo2(data)  /* fills in attributes and features */
{ document.search_form.attrfeatvalue.innerHTML = data;
}

/* gather id's of products displayed in product-edit. these can be used in copy-combi */
/* this function is a bit more complicated as it is also used by prodcombi-edit that has the same product id more than once */
/* in order to get the right order in sorted tables the id of the first td is retrieved */
function gather_prod_ids()
{ var products = [];
  var gfield = document.getElementById("gatherer");
  var rows = document.getElementById("Maintable").rows.length;
  for(var i=0; i < rows; i++)
  { var rowid = Maintable.rows[i].cells[0].id.substring(4);
    if(rowid=='') continue;
    var prod_id = eval("Mainform.id_product"+rowid);
	if(!prod_id) { alert("something went wrong"+Maintable.rows[i].cells[0].innerHTML); crash();}
	if(products.indexOf(prod_id) == -1)
      products.push(prod_id.value);
  }
  var flag = gatherform.modus.value;
  if(flag != "asshown")
	  products.sort(compareNumbers);
  if(flag == "ranges")
  { var tmp = '';
	var lastprod = -1;
	var inrange = 0;
	var rangecount = 0;
    products.forEach(product =>
	{ if((product-lastprod) == 1)
	  { inrange=true;
        rangecount++;
	  }
	  else
	  { if(inrange)
		{ if(rangecount == 1)
		    tmp += ","+lastprod+","+product;
		  else
		    tmp += "-"+lastprod+","+product;
		}
	    else
		{ if(lastprod != -1)
			tmp += ",";
		  tmp += product;
		}  
		inrange = false;
		rangecount = 0;
	  }
	  lastprod=product;
	});
	gfield.value=tmp;
  }
  else
    gfield.value=products.join();
}

function compareNumbers(a, b) 
{ return a - b;
}

/* handle changes in the order (Sort By) field in the search block */
function change_order()
{ if(document.search_form.order.selectedIndex < 8) 
	document.search_form.rising.selectedIndex = 0;
  else
  	document.search_form.rising.selectedIndex = 1;
}

/* insert_translation is used by the translation plugin */
function insert_translation(field, mytrans)
{ var fld = eval('Mainform.'+field);
  fld.value=mytrans;
  reg_change(fld);
}

/* set mode in translate mass edit field */
function settransposemode(elt)
{ var tmp = document.getElementById("transspan");
  if(elt.checked)
  {  tmp.style.display = "none";
     Mainform.transposemode.value=1;
  }
  else
  {  tmp.style.display = "inline";  
     Mainform.transposemode.value=0;
  }
}

/* handle changes in the search fields in the search block */
function change_sfield(elt)
{ var value = elt.value;
  var name = elt.name;
  if(name=="search_fld1")
	  var target = search_form.search_cmp1;
  else if(name=="search_fld2")
	  var target = search_form.search_cmp2;
  else
	  var target = search_form.search_cmp3;
  var numericfields = ["cp.position","ps.price","priceVAT","s.quantity","ps.indexed","ps.active","t.rate","ps.online_only"];
  if((target.selectedIndex == 0) || (target.selectedIndex == 4))
  { if((value.indexOf('id_') >= 0) || (numericfields.indexOf(value) >= 0))
  	  target.selectedIndex = 4; /* = */
    else
  	  target.selectedIndex = 0; /* IN */
  }
}

	function newwin_check()
	{ if(search_form.newwin.checked)
		search_form.target = "_blank";
	  else
		search_form.target = "";
	}
	  
	/* swapfeatures adds/removes a row with feature field names to the field names block in the search block */
	function swapFeatures(elt)
	{ var myrow, i;
	  if ((elt.checked) && (prestools_missing.indexOf("features") !== -1))
	      alert("Features is a plugin that needs to be bought seperately at www.Prestools.com.\nWithout the plugin you are in demo-mode: you can make changes but they will not be saved!");
	  for(i=0; i<9; i++)
	  { if(myrow = document.getElementById("featureblock"+i))
		{ if(elt.checked)
		    myrow.style.display = "table-row";
		  else
		  { myrow.style.display = "none";
			var elts = myrow.getElementsByTagName("input");
			for(j=0; j<elts.length; j++)
			    elts[j].checked = false;
		  }
		}
	  }
	}
  
  /* swapStats adds/removes a row with statistics fields to the field names block in the search block */
  function swapStats(elt)
  { var myrow = document.getElementById("statsblock");
	if(elt.checked)
	  myrow.style.display = "table-row";
	else
	{ myrow.style.display = "none";
	  var elts = myrow.getElementsByTagName("input");
	  for(j=0; j<elts.length; j++)
		elts[j].checked = false;
	}
  }
  
  /* this function is called after clicking the "List products" button and produces a product overview that can be used for stock taking */
  function ListProducts()
  { var div = document.getElementById("listsearchdiv");
    var block = document.getElementById("searchblock");
	var p = block.cloneNode(true);
    div.appendChild(p);
	listerform.verbose.value = SwitchForm.verbose.checked;
	listerform.submit();
	div.innerHTML = "";
  }
  
  function submitCSV()
  { var div = document.getElementById("csvsearchdiv");
    var block = document.getElementById("searchblock");
	var p = block.cloneNode(true);
    div.appendChild(p);
	csvform.verbose.value = SwitchForm.verbose.checked;
	
    var selects = block.getElementsByTagName('select');
    for(var k=0;k<selects.length;k++)  
    { if(!selects[k].name) continue;
      document.csvform[selects[k].name].selectedIndex = selects[k].selectedIndex;
    }
	
	csvform.submit();
	div.innerHTML = "";
  }
  
  function importCSV()
  { csvimportform.verbose.value = SwitchForm.verbose.checked;
	var tbl = document.getElementById("offTblBdy");
	var len = tbl.rows.length;
	var myprods = [];
	var myprodidxs = [];
	var found = 0;
	for(var i=0; i<len; i++)
	{ if(tbl.rows[i].innerHTML == "<td></td>") {continue; }
	  myprods.push(tbl.rows[i].childNodes[0].childNodes[1].value);
	  myprodidxs.push(tbl.rows[i].childNodes[0].childNodes[1].name.substring(10));
	  found = i;
	}
	csvimportform.myprods.value = myprods.join();
	csvimportform.myprodidxs.value = myprodidxs.join();	
	
	var tblmain = document.getElementById("Maintable");
	var myfields = [];
	len = tbl.rows[found].cells.length;
	for(var i=2; i<len-1; i++)
	{ var switcher = eval("SwitchForm.disp"+i);
      if(!switcher)
		myfields.push(tblmain.tHead.rows[0].cells[i].firstChild.getAttribute("fieldname"));
	}
	csvimportform.myfields.value = myfields.join();	
	csvimportform.submit();
  }
  
  /* prepare_update is a callback function used by copyform and translateform */
  function prepare_update()
  { var prod_base, id_product;
    records = new Array(); /* leave this global! */
	for(var i=0; i < numrecs; i++) 
	{ prod_base = eval("document.Mainform.id_product"+i);
	  if(!prod_base) continue;
	  id_product = prod_base.value;
	  records[id_product] = i;
	}
  }
  
  /* this function is called from the iframe to update language fields from a different language */
  function update_field(product, field, value)
  { var base = eval("document.Mainform."+field+records[product]);
	if(base.value != value)
		reg_change(base);
	base.value = value;
  }
  
	 //&id_category=&fields%5B%5D=name&fields%5B%5D=VAT&fields%5B%5D=category&fields%5B%5D=description&fields%5B%5D=price&fields%5B%5D=ean&fields%5B%5D=image&fields%5B%5D=description_short
	  
  function massUpdate()
  { var i, j, k, x, y, tmp, base, changed,myval,myval2, oldvalue, oldvalue2, custom, oldcustom;
    var shop, currency, country, group, customer, quantity, datefrom, dateto, targetprice;
	var targetpriceVAT, discount_delayed, reductiontax, copyfield, cellindex, src, src2; 
	var orig, pos, id_product, fieldtext;
	base = eval("document.massform.field");
	/* fieldtext is the recognizer. fieldname is the formfield that is to be updated */
	var fieldname = fieldtext = base.options[base.selectedIndex].value;
	var tbl= document.getElementById("offTblBdy");
	if(fieldtext.substr(1,8) == "elect a "){ alert("You must select a fieldname!"); return;}
	base = eval("document.massform.action");
	var action = base.options[base.selectedIndex].text;
	if(action.substr(1,8) == "elect an") { alert("You must select an action!"); return;}
	if(document.massform.myvalue) /* default: after this come the specific actions and fieldtexts */
	{ myval = document.massform.myvalue.value;
	  if(((fieldtext == "price") || (fieldtext == "priceVAT")) && !isNumber(myval)) 
	  { alert("Only numeric prices are allowed!\nUse decimal points!"); 
	    return;
	  }
	}
	if(action == "copy from other lang")
	{ let products = new Array();
	  let fields = new Array();
	  fields[0] = fieldtext;

	  j=0; k=0;
	  for(i=0; i < numrecs; i++) 
	  { prod_base = eval("document.Mainform.id_product"+i);
		if(!prod_base) continue;
		id_product = prod_base.value;
		products[k++] = id_product;
	  }
	  document.copyForm.products.value = products.join(",");
	  document.copyForm.fields.value = fields.join(",");
	  document.copyForm.id_lang.value = massform.copylang.value;		  
	  document.copyForm.submit(); /* copyForm comes back with the prepare_update() function */
	  return;
	}
	if(action == "translate")
	{ if(Mainform.transposemode.value == 0)
	  { let oldlang = massform.oldlang.value;
	    let newlang = massform.newlang.value;
	    if((oldlang==0) || (newlang==0) || (oldlang==newlang))
	    { alert("Please, make a valid choice!");
		  return;
		}
	  }
	  Mainform.target = "tank";
	  Mainform.action = "TE_plugin_translate.php";
	  Mainform.oldlang.value = massform.oldlang.value;
	  Mainform.newlang.value = massform.newlang.value;
	  Mainform.submit(); /* translateForm comes back with prepare_update() function */
	  Mainform.target = "_self";
	  Mainform.action = "product-proc.php";
	  return;
	}
	if(action == "round")
	{ var massupper = document.massform.massupper.value;
	}
	if(action == "replace")
	{ var oldval = document.massform.oldval.value;
	}
	if(((fieldtext == "description") || (fieldtext == "description_short")) && (action == "set") && (myval.length != 0) &&(myval.substring(0,2)!="<p"))
			myval = "<p>"+myval+"</p>";
	if((fieldtext == "VAT") || (fieldtext == "manufacturer"))
		myval = document.massform.myvalue.selectedIndex;
	if(fieldtext == "stockflags")
	{	myval = document.massform.myvalue.value;
		if(myval=="3")
			mywarehouse = massform.stockflags_warehouse.value;
	}
	if((action == "add") && (fieldtext == "discount"))
	{	shop = massform.shop.options[massform.shop.selectedIndex].value;
		currency = massform.currency.options[massform.currency.selectedIndex].value;
		country = massform.country.options[massform.country.selectedIndex].value;
		group = massform.group.options[massform.group.selectedIndex].value;			
		customer = massform.customer.value;
		price = massform.price.value;
		quantity = massform.quantity.value;
		reduction = massform.reduction.value;
		reductiontax = massform.reductiontax.value;
		reductiontype = massform.reductiontype.options[massform.reductiontype.selectedIndex].text;
		datefrom = massform.datefrom.value;
		dateto = massform.dateto.value;
		discount_delayed = [];
		setTimeout(function(){delayed_discount_change("reduction");}, 100);
	}
	if((action == "remove") && (fieldtext == "discount"))
	{	var subfieldname = massform.fieldname.options[massform.fieldname.selectedIndex].value;
		var subfield = massform.subfield.value;		
	}
	if((action == "add fixed target discount") && (fieldtext == "discount"))
	{	shop = massform.shop.options[massform.shop.selectedIndex].value;
		currency = massform.currency.options[massform.currency.selectedIndex].value;
		country = massform.country.options[massform.country.selectedIndex].value;
		group = massform.group.options[massform.group.selectedIndex].value;	
		customer = massform.customer.value;
		quantity = massform.quantity.value;
		datefrom = massform.datefrom.value;
		dateto = massform.dateto.value;
		targetprice = massform.targetprice.value;
		targetpriceVAT = massform.targetpriceVAT.value;
		discount_delayed = [];
		if(targetprice != "")
		{ reductiontax = "0";
		  setTimeout(function(){delayed_discount_change("newprice_excl");}, 100);
		}
		else if(targetpriceVAT != "")
		{ reductiontax = "1";
		  setTimeout(function(){delayed_discount_change("newprice_incl");}, 100);
	    }
		else return; /* neither field had a value */
		reductiontype = "amt";
		/* the following is set pro forma. It's real setting will happen in delayed_discount_change() */
		reduction = 0; 
	}
	if((fieldtext == "active") || (fieldtext == "on_sale") || (fieldtext == "online_only") || (fieldtext=="show_condition") || (fieldtext=="ls_alert"))
	{	myval = document.massform.myvalue.checked;
	}
	if(fieldtext == "category")
	{	myval = document.massform.myvalue.value;
		fieldname = "categorysel"; /* needed because we check the field for existence/edibility at the beginning of the loop */
	}
	if(fieldtext == "redirect")
	{ var mysel = document.massform.myvalue_sel.selectedIndex;
	  myval = document.massform.myvalue.value;
	}
	if(fieldtext == "supplier")
	{	myval = document.massform.myvalue_sel.value;
		fieldname = "suppliersel"; /* needed because we check the field for existence/edibility at the beginning of the loop */
		if((action=="set") || (action=="increase%") || (action=="increase amount"))
			myval2 = document.massform.myvalue.value;
	}
	if(fieldtext == "attachmnts")
	{	myval = document.massform.myvalue.value;
		fieldname = "attachmentsel"; /* needed because we check the field for existence/edibility at the beginning of the loop */
	}
	if(fieldtext == "carrier")
	{	myval = document.massform.myvalue.value;
		fieldname = "carriersel"; /* needed because we check the field for existence/edibility at the beginning of the loop */
	}
	if(fieldtext == "customizations")
	{ var id_lang = Mainform.id_lang.value;
	  var langids = lang_ids.split(',');
	  var len = langids.length;
	  var langindex = -1;
	  for(let i=0; i<len; i++)
	  { if(langids[i] == id_lang)
	      langindex = i;
	  }
	  if(langindex == -1)
		alert('Error determining langindex');
	}
	
	if(fieldtext == "image")
	{ if((action == "set") || (action == "copy from field") || (action == "replace from field"))
	  { var emptyonly = document.massform.emptyonly.checked;
		var coverage = document.massform.coverage.value;
	  }
	  else if(action="remove")
	  { var covers = document.massform.covers.checked;
		var others = document.massform.others.checked;
		var legtext = document.massform.legtext.value;
	  }
	}
	if(fieldtext == "virtualp")
	{	//var is_virtual = document.massform.is_virtual.value;
		var date_expiration = document.massform.date_expiration.value;
		var nb_days_accessible = document.massform.nb_days_accessible.value;
		var nb_downloadable = document.massform.nb_downloadable.value;
	}
	if((action == "copy from field") || (action == "replace from field"))
	{	if(massform.copyfield.selectedIndex == 0)
		{ alert("You must select a field to copy from!");
		  return;
		}
		copyfield = massform.copyfield.options[massform.copyfield.selectedIndex].value;
		if(copyfield == "id_category")
			cellindex = getColumn("category");
		else if(copyfield != "id_product")
		    cellindex = getColumn(copyfield);
		if(action == "replace from field")
		{ oldval = document.massform.oldval.value;
		  if(!document.massform.myregexp.checked)
			oldval = oldval.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g, "\\$&");
	    }
		if(copyfield != "id_product")
		{ tmp = eval("SwitchForm.disp"+cellindex);
		  if(!tmp)
		  { alert("The field which you copy or replace from should not be in editable mode!");
		    return;
		  }
		}
	}
	if((fieldtext.match(/feature[0-9]*field/)) && (!multi_feature))
	{	custom = 1;
		if((document.massform.myvalue_sel) && (document.massform.myvalue_sel.selectedIndex!=0))
		{ custom=0;
		  myval = document.massform.myvalue_sel.selectedIndex;
		}
		if(action == "replace")
		{ oldcustom = 1;
		  oldval = document.massform.oldval.value;
		  if((document.massform.oldval_sel) && (document.massform.oldval_sel.selectedIndex!=0))
		  { oldcustom=0;
			oldval = document.massform.oldval_sel.selectedIndex;
		  }
		  if(custom != oldcustom)
		  { alert("The old and the new values should be of the same type: either select or freetext!");
			return;
		  }
		}
	}
	if((fieldtext.match(/feature[0-9]*field/)) && (multi_feature))
	{	custom = 1;
		if((document.massform.myvalue_sel) && (document.massform.myvalue_sel.selectedIndex!=0))
		{ custom=0;
		  myval = document.massform.myvalue_sel.value;
		  myval3 = document.massform.myvalue_sel[document.massform.myvalue_sel.selectedIndex].text;
		}
		if(action == "replace")
		{ oldcustom = 1;
		  oldval = document.massform.oldval.value;
		  if((document.massform.oldval_sel) && (document.massform.oldval_sel.selectedIndex!=0))
		  { oldcustom=0;
		    oldval = document.massform.oldval_sel.value;
		    oldval3 = document.massform.oldval_sel[document.massform.oldval_sel.selectedIndex].text;
		  }
		}
		if((((custom==0) && (myval==0)) || ((custom==1) && (myval=="") && (action!="replace"))) && (action != "set")) return;
	}
//	alert("DDD "+fieldtext+" === "+action );
	for(i=0; i < numrecs; i++)
	{ 	if(Mainform.selectmodestatus.value == 1) /* do not submit unchecked rows in checkbox mode */
	    { var fld = eval("Mainform.sel"+i);
		  if(!fld.checked)
			  continue;
	    }
	    if(action == "touch")
	    { var row = tbl.rows[i];
		  row.cells[0].setAttribute("changed", "1");
		  row.style.backgroundColor="#DDD";
	    }
		else
		  changed = false;
		if(fieldname == "discount")
		   fieldname = "discount_count";
		if(fieldname == "customizations")
		   fieldname = "custom_count";
		if(fieldname == "redirect")
		   fieldname = "redirect_type";
		if(fieldname == "image")
		  field = eval("document.Mainform.image_set"+i);
		else if(fieldname == "shopz")
		{ field = document.getElementsByName("shopz"+i+"[]");
	    }
		else if(fieldname == "virtualp")
		  field = eval("document.Mainform.date_expiration"+i); /* field must have a value and virtualp is not a fieldname */
		else if((fieldtext.match(/feature[0-9]*field/)) && (multi_feature))
		  field = eval(document.getElementsByName(fieldname+'_count'+i))[0];
 	    else
		  field = eval("document.Mainform."+fieldname+i);
		if(!field) { continue; } /* deal with clicked away lines */
		if(((fieldname == "description") || (fieldtext == "description_short")) && (field.parentNode.childNodes[0].tagName == "DIV"))
			field.value = tinyMCE.get(fieldname+i).getContent();
		if(fieldname == "image")	
		{ if((action == "copy from field") || (action == "replace from field"))
		  { if(copyfield == "id_product")
			  myval2 = get_product_id(i);
		    else 
			{ myval2 = tbl.rows[i].cells[cellindex].innerHTML;	
			  if(copyfield == "category")
			  { tmp = myval2.match(/<a title=\"([^\"]*)\" href=\"[^\"]*\" target=\"_blank\" tabindex=\"-1\">/);
			    myval2 = tmp[1];
		      }	
			  else if(copyfield == "id_category")
			  { tmp = myval2.match(/<a title=\"([^\"]*)\" href=\"[^\"]*\" target=\"_blank\" tabindex=\"-1\">([^<]*)</);
			    myval2 = tmp[2];
		      }	
			  else
				  myval2 = striptags(myval2);
			}			  
		  }
		  if((action == "copy from field") || (action == "replace from field") || (action == "set"))
		  { var imgset = field.value;
		    var images = imgset.split(",");
		    tmp = eval('Mainform.image_default'+i);
		    var imgdefault = tmp.value;
		    for(j=1; j<images.length-1; j++)
		    { tmp = eval('Mainform.image_legend'+images[j]+'s'+i);
			  var legend = tmp.value;
			  if(((legend=="") || (!emptyonly)) && ((images[j]==imgdefault) || (coverage == "all")))
			  { if(action == "copy from field")
			    { tmp.value = myval2;
				  if(legend != myval2) changed = true;
			    }
			    else if(action == "replace from field")
			    { src = document.massform.oldval.value;
			      src2 = src.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g, "\\$&");
			      if(document.massform.myregexp.checked)
			        tmp.value = legend.replace(new RegExp(src, 'g'), myval2);
			      else
			        tmp.value = legend.replace(new RegExp(src2, 'g'), myval2);  
 			      if(legend != tmp.value) changed = true;		
			    }
			    else if(action == "set")
			    { tmp.value = myval;
 			      if(legend != tmp.value) changed = true;
				}				  
			  }
			}
		  }
		  if(action == "remove")
		  { var imgset = field.value;
		    var images = imgset.split(",");
		    tmp = eval('Mainform.image_default'+i);
		    var imgdefault = tmp.value;
		    for(j=1; j<images.length-1; j++)
			{ tmp = eval('Mainform.image_legend'+images[j]+'s'+i);
			  var legend = tmp.value;
			  var haddeletion = false;
			  if((((images[j]==imgdefault) && covers) || ((images[j]!=imgdefault) && others)) && ((legtext == "") || (legend.indexOf(legtext) > -1)))
			  { var statusfield = eval('Mainform.image_status'+images[j]+'s'+i);
				if(covers && !others && haddeletion) /* when an image is deleted another image becomes default. Without the haddeletion check this can cause all images to be deleted. */
					continue;
				if(statusfield.value != "deleted")
				{ var baseimg = document.getElementById('baseimg'+images[j]+'s'+i);
				  del_image(i, baseimg);
				  haddeletion = true;
				}
			  }
			}
		  }
		}
		else if((fieldtext == "accessories") && (action!="set"))
		{ if(field.value != '')
		    var accs = field.value.split(',');
		  else
		    var accs = [];
		  if(action == 'add')
		  { if(accs.indexOf(myval) == -1)
			{ accs.push(myval);
			  field.value = accs.join(',');
			  changed = true;	
			}
		  }
		  else if(action == "remove")
		  { const index = accs.indexOf(myval);
			if (index > -1) 
			{ accs.splice(index, 1);
			  field.value = accs.join(',');
			  changed = true;
			}
		  }
		  else if(action == "replace")
		  { const index = accs.indexOf(oldval);
			if (index > -1) 
			{ accs[index] = myval;
			  myval2 = accs.join(',');
			}
		  }
		}		
		else if((fieldtext == "redirect") && (action=="set"))
		{ var redirect_type = eval('Mainform.redirect_type'+i);
		  if(redirect_type.selectedIndex != mysel)
		  { redirect_type.selectedIndex = mysel;
			changed = true;
		  }
		  var targetid = eval('Mainform.id_redirected'+i);
		  if((myval != '') && (targetid.value != myval))
		  { targetid.value = myval;
			changed = true;
		  }
		}
		else if((fieldtext == "supplier") && ((action=="set") || (action=="increase%") || (action=="increase amount")))
		{ var supplierset = eval('Mainform.mysups'+i+'.value');
		  var attributeset = eval('Mainform.supplier_attribs'+i+'.value');
		  var suppliers = supplierset.substring(1).split(',');
		  var attributes = attributeset.split(',');
		  for(x=0; x<suppliers.length; x++)
		  { if((myval != 0) && (myval != suppliers[x])) continue;
			for(y=0; y<attributes.length; y++)
			{ base = eval('Mainform.supplier_price'+attributes[y]+'t'+suppliers[x]+'s'+i);
			  oldvalue = base.value;
			  if(action == "set")
			    base.value = myval2;
			  else if(action == "increase%")
			  { tmp = base.value * (parseFloat(myval2)+100);
				base.value = tmp / 100;
			  }
			  else if(action == "increase amount")
			  { base.value = parseFloat(base.value) + parseFloat(myval2);
			  }
			  if(oldvalue != base.value)
			    changed = true;  
			}
		  }
		}
		else if(action == "insert before")
		{	if(((fieldname == "description") || (fieldtext == "description_short")) 
			   && (myval.indexOf('<') == -1) && (myval.indexOf('>') == -1))
			{   if(myval.substring(0,3) == "<p>")
				{ myval2 = myval+field.value;
				}
				else
				{ orig = field.value.replace(/^<p>/, "");
				  myval2 = "<p>"+myval+orig;
				}
			}
			else
				myval2 = myval+field.value;
			changed = true;
		}
		else if(action == "increase%")
		{ tmp = field.value * (parseFloat(myval)+100);
		  myval2 = tmp / 100;
		  if(myval2 != 0)
			changed = true;
		}
		else if(action == "increase amount")
		{ myval2 = parseFloat(field.value) + parseFloat(myval);
		  if(fieldname == "qty")
			myval2 = parseInt(myval2);
		  if(myval2 != field.value)
			changed = true;
		}	
		else if(action == "round")
		{ myval2 = parseFloat(field.value);
		  if(massupper == "up") myval2 = myval2+0.000000001; else myval2 = myval2-0.000000001;
		  var mul = 1+'e'+myval;
		  myval2=Math.round(myval2*mul)/mul;  
		  if(myval2 != field.value)
			changed = true;
		}
		else if(action == "insert after")
		{	if((fieldname == "description") || (fieldtext == "description_short"))
			{	if( myval.charAt(0) == "<") /* new alinea */
				{	myval2 = field.value+myval;
				}
				else	/* insert in last alinea */
				{	orig = field.value.replace(/<\/p>$/, "");
					myval2 = orig+myval+"</p>";
				}
			}
			else
				myval2 = field.value+myval;
			changed = true;
		}
		else if(action == "strip html")
		{ var tmp = document.createElement("DIV");
		  tmp.innerHTML = eval("document.Mainform."+fieldname+i+".value");
		  myval2 = tmp.textContent || tmp.innerText || "";
		}
		else if(action == "balance html")
		{ myval2 = tidy_html(eval("document.Mainform."+fieldname+i+".value"));
		}		
		else if(action == "TinyMCE")
		{	field = eval("document.Mainform."+fieldname+i);
			useTinyMCE(field, fieldname+i);
		}
		else if(action == "TinyMCE-deluxe")
		{	field = eval("document.Mainform."+fieldname+i);
			useTinyMCE2(field, fieldname+i);
		}
		else if(action == "regenerate")
		{	field = eval("document.Mainform."+fieldname+i);
			oldvalue = field.value;
			var namecol = getColumn("name");
			if(namecol == -1) return;
			var node = field.parentNode.parentNode.cells[namecol].childNodes[0];
			if(node.nodeName == "INPUT")
			{ alert("For link-rewrite regeneration the Name field can not be editable!")
			  return;
			}
			myval2 = str2url(node.innerHTML);
			if(oldvalue != myval2)
			  changed = true;
		}
		else if((action == "replace") 
			&& (((pos = featurelist.indexOf(fieldtext)) == -1) || (!multi_feature)))
		{ 	if((pos = featurelist.indexOf(fieldtext)) != -1)
			{  if (custom == 1)
			  { // The replace string was found at http://dumpsite.com/forum/index.php?topic=4.msg8#msg8 and http://stackoverflow.com/questions/2116558/fastest-method-to-replace-all-instances-of-a-character-in-a-string
			    if(document.massform.myregexp.checked)
				  src2 = oldval;
			    else
				  src2 = oldval.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g, "\\$&");
				oldvalue = field.value;
				myval2 = field.value.replace(new RegExp(src2, 'g'), myval);
			  }
			}
			else if(fieldtext == "customizations")
			{ var count_root = eval("Mainform.custom_count"+i);
		      var ccount = parseInt(count_root.value);
			  var tabs = count_root.parentNode.getElementsByTagName('TABLE');
			  var tabsubs = tabs[0].rows.length;
		      for(x=0; x<tabsubs; x++)
			  { var langs = tabs[0].rows[x].getElementsByTagName('TEXTAREA');
			    if(langs[langindex].value == myval)
  		        { var fld = eval('Mainform.custom_req'+x+'s'+i);
				  if(massform.custom_req.checked)
					fld.checked = true;
				  else
					fld.checked = false;
				  fld = eval('Mainform.custom_type'+x+'s'+i);
				  fld.value = massform.custom_type.value;
			  
				  var len = langids.length;
				  for(var y=0; y<len; y++)
				  { let fld = eval('Mainform.custom_name'+langids[y]+'x'+x+'s'+i);
			        var source = eval('massform.custom_name'+langids[y]);
				    fld.value = source.value;
			      }
			      changed = true;
			    }
			  }
			}
			else
			{ src = document.massform.oldval.value;
			  src2 = src.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g, "\\$&");
			  oldvalue = field.value;
			  if(document.massform.myregexp.checked)
			    myval2 = field.value.replace(new RegExp(src, 'g'), myval);
			  else
			    myval2 = field.value.replace(new RegExp(src2, 'g'), myval);  
 			  if(oldvalue != myval2)
				changed = true;		
			}
		}
		else if(action == "set as default")  /* set category or supplier as default */
		{ 	if(fieldtext == "category")
			{ var list = document.getElementById("categorysel"+i);
		      len = list.length;
			  for(x=0; x<len; x++)
			  { if(list[x].value == myval)
				{ list.selectedIndex = x;
				  MakeCategoryDefault(i); 
				  changed = true;
				  break;
				}
			  }
			}
		 	else if(fieldtext == "supplier")
			{ var list = document.getElementById("suppliersel"+i);
		      len = list.length;
			  for(x=0; x<len; x++)
			  { if(list[x].value == myval)
				{ list.selectedIndex = x;
				  MakeSupplierDefault(i); 
				  changed = true;
				  break;
				}
			  }	  
		  }
		}
		else if(action == "add")  /* add category or discount to product */
		{ 	if(fieldtext == "attachmnts")
			{ var list = document.getElementById("attachmentlist"+i);
			  len = list.length;
			  for(x=0; x<len; x++)
			  { if(list[x].value == myval)
				{ list.selectedIndex = x;
				  Addattachment(i, 1);
				  changed = true;
				  break;
				}
			  }
			}
			else if(fieldtext == "carrier")
			{ var list = document.getElementById("carrierlist"+i);
			  len = list.length;
			  for(x=0; x<len; x++)
			  { if(list[x].value == myval)
				{ list.selectedIndex = x;
				  Addcarrier(i);
				  changed = true;
				  break;
				}
			  }
			}
			else if(fieldtext == "category")
			{ var list = document.getElementById("categorylist"+i);
			  len = list.length;
			  for(x=0; x<len; x++)
			  { if(list[x].value == myval)
				{ list.selectedIndex = x;
				  Addcategory(i);
				  changed = true;
				  break;
				}
			  }
			}
		    else if(fieldtext == "customizations")
		    { var count_root = eval("Mainform.custom_count"+i);
		      var ccount = parseInt(count_root.value);
			  add_customization(i);
			  var field = eval('Mainform.custom_req'+ccount+'s'+i);
			  if(massform.custom_req.checked)
			    field.checked = true;
			  else
			    field.checked = false;
			  var field = eval('Mainform.custom_type'+ccount+'s'+i);
			  field.value = massform.custom_type.value;
			  
			  var len = langids.length;
			  for(var x=0; x<len; x++)
			  { var field = eval('Mainform.custom_name'+langids[x]+'x'+ccount+'s'+i);
			    var target = eval('massform.custom_name'+langids[x]);
				field.value = target.value;
			  }
			  changed = true;
			}		 
			else if (fieldtext == "discount")
			{  	var count_root = eval("Mainform.discount_count"+i);
				var dcount = parseInt(count_root.value);
/* function 			 fill_discount(row,entry, id,status,shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,	to,newpricex,newpricei)             */
				var blob = fill_discount(i,dcount,"","new", shop,"",	   currency,country,group,customer,	price,quantity,reduction,reductiontax,reductiontype,datefrom,dateto,0,     0);
				var new_div = document.createElement("div");
				new_div.innerHTML = blob;
				var adder = document.getElementById("discount_adder"+i);
				adder.parentNode.insertBefore(new_div,adder);
				discount_delayed.push([i, dcount]);
				count_root.value = dcount+1;
				changed = true;
			}
			else if(fieldname == "shopz")
			{ var chklength = field.length;             
			  for(var k=0;k< chklength;k++)
			  { if((field[k].value == myval) && !field[k].checked)
				{ field[k].checked = true;
				  changed = true;
			    }
			  }
			  field = field[0]; /* prepare for reg_change call */
			}
			else if(fieldtext == "supplier")
			{ var list = document.getElementById("supplierlist"+i);
			  len = list.length;
			  for(x=0; x<len; x++)
			  { if(list[x].value == myval)
				{ list.selectedIndex = x;
				  Addsupplier(i, 1);
				  changed = true;
				  break;
				}
			  }
			}
			else if (fieldtext == "tags")
			{ myval = myval.substring(0,32);
			  if(field.value != "")
			  { myval2 = field.value.split(",");
				var tagfound = false;
				for (x = 0; x < myval2.length; x++) 
				{ if(myval2[x] == myval)
					tagfound = true;
				}
				if(tagfound) continue;
				myval2[x] = myval;
				field.value = myval2.join(",");
			  }
			  else
				field.value = myval;
			  changed = true;
			}
		}
		else if ((action=="add fixed target discount") && (fieldtext == "discount"))
		{  	/* first we need to know the old price */
			var pricecol = getColumn("price");
			if(pricecol == -1) {alert("Price column must be present!"); return;}
			var baseprice = parseFloat(tbl.rows[i].cells[pricecol].innerHTML);
			var VATcol = getColumn("VAT");
			if(VATcol == -1)
			  var VAT = 0;
			else 
			  var VAT = parseFloat(tbl.rows[i].cells[VATcol].innerHTML);
			if((targetprice != "") && (parseFloat(targetprice) >= baseprice)) continue;
			if((targetpriceVAT != "") && (parseFloat(targetpriceVAT) >= (baseprice * (1 + (VAT/100))))) continue;
			var count_root = eval("Mainform.discount_count"+i);
			var dcount = parseInt(count_root.value);
/* function 		 fill_discount(row,entry, id,status,shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,	to,newpricex,newpricei)             */
			var blob = fill_discount(i,dcount,"","new", shop,"",	   currency,country,group,customer,	   	"",	quantity,reduction,reductiontax,reductiontype,datefrom,dateto,targetprice, targetpriceVAT);
			var new_div = document.createElement("div");
			new_div.innerHTML = blob;
			var adder = document.getElementById("discount_adder"+i);
			adder.parentNode.insertBefore(new_div,adder);
			discount_delayed.push([i, dcount]);
			count_root.value = dcount+1;
			changed = true;
		}
		else if((fieldtext == "price") && (action == "recalculate excl price"))
		{ var oldVAT = parseFloat(myval);
		  var VATcol = getColumn("VAT");
		  if(VATcol == -1) {alert("No VAT Column found!"); return; }
		  field = eval("document.Mainform."+fieldname+i);
		  var newVAT = parseFloat(field.parentNode.parentNode.cells[VATcol].innerHTML);
		  var oldvalue = parseFloat(field.value);
		  myval2 = oldvalue*(100+oldVAT)/(100+newVAT);
		  myval2 = myval2.toFixed(6);
		  if(oldVAT != newVAT)
			changed = true;
		
		}
		else if(action == "remove") 
		{ if(fieldtext == "attachmnts") /* attachment remove */
		  { var list = document.getElementById("attachmentsel"+i);
			len = list.length;
			for(x=0; x<len; x++)
			{ if(list[x].value == myval)
			  { list.selectedIndex = x;
				Removeattachment(i);
				changed = true;
				break;
			  }
			}
		  }
		  else if(fieldtext == "carrier")			/* remove carrier from product */
		  {	var list = document.getElementById("carriersel"+i);
			len = list.length;
			for(x=0; x<len; x++)
			{ if(list[x].value == myval)
			  { list.selectedIndex = x;
				Removecarrier(i);
				changed = true;
				break;
			  }
			}
		  }
		  else if(fieldtext == "category")			/* remove category from product */
		  {	var list = document.getElementById("categorysel"+i);
			len = list.length;
			for(x=0; x<len; x++)
			{ if(list[x].value == myval)
			  { list.selectedIndex = x;
				Removecategory(i);
				changed = true;
				break;
			  }
			}
		  }
		  else if(fieldtext == "customizations")
		  { var count_root = eval("Mainform.custom_count"+i);
		    var ccount = parseInt(count_root.value);
			var tabs = count_root.parentNode.getElementsByTagName('TABLE');
			var tabsubs = tabs[0].rows.length;
		    for(x=0; x<tabsubs; x++)
			{ var langs = tabs[0].rows[x].getElementsByTagName('TEXTAREA');
			  if(langs[langindex].value == myval)
  		      { del_customization(i,x);
			  }
			}
		  }		  
		  else if (fieldtext == "discount")	/* discount remove from product */
		  { var count_root = eval("Mainform.discount_count"+i);
			var dcount = parseInt(count_root.value);
			for(x=0; x<dcount; x++)
			{ var subroot = eval("Mainform.discount_"+subfieldname+x+"s"+i);
			  if(!subroot) continue; /* happens with deleted discounts */
			  var subvalue = subroot.value;
			  if(subvalue == subfield)
			  { del_discount(i,x);
			  }
			}
		  }
		  else if(fieldname == "shopz")		/* remove shop from product */
		  { var chklength = field.length;             
			for(var k=0;k< chklength;k++)
			{ if((field[k].value == myval) && field[k].checked)
			  { field[k].checked = false;
			    changed = true;
			  }
			}
			field = field[0]; /* prepare for reg_change call */
		  }	
		  else if(fieldtext == "supplier")			/* remove supplier from product */
		  {	var list = document.getElementById("suppliersel"+i);
			len = list.length;
			for(x=0; x<len; x++)
			{ if(list[x].value == myval)
			  { list.selectedIndex = x;
				Removesupplier(i);
				changed = true;
				break;
			  }
			}
		  }
		  else if (fieldtext == "tags")	/* tags remove */
		  { myval2 = field.value.split(",");
			var tagfound = false;
			for (index = 0; index < myval2.length; index++) 
			{ if(myval2[index] == myval)
			  { myval2.splice(index,1);
				tagfound=true;
				break;
			  }
			}
			if(!tagfound) continue;
			field.value = myval2.join(",");
			changed = true;
		  }
		}
		else if((action == "replace from field") && (((pos = featurelist.indexOf(fieldtext)) == -1) || (!multi_feature)))
		{ oldvalue = field.value;
		  if(copyfield == "id_product")
			myval2 = get_product_id(i);
		  else 
		  { myval2 = tbl.rows[i].cells[cellindex].innerHTML;
		    myval2 = processcopyfield(copyfield, myval2, fieldtext)
		  }
		  let evax = new RegExp(oldval,"g");
		  myval2 = field.value.replace(evax, myval2);
		  if(oldvalue != myval2) changed = true;
		}
		else if((action == "copy from field") && (((pos = featurelist.indexOf(fieldtext)) == -1) || (!multi_feature)))
		{ oldvalue = field.value;
		  if(copyfield == "id_product")
			myval2 = get_product_id(i);
		  else 
		  { myval2 = field.parentNode.parentNode.cells[cellindex].innerHTML;
		    myval2 = processcopyfield(copyfield, myval2, fieldtext)
		  }
		  if(oldvalue != myval2) changed = true;
		}
		else if(fieldtext == "virtualp")
		{ oldvalue = field.value;
		  if(date_expiration != "")
		  { if(date_expiration != field.value) changed = true;
			field.value = date_expiration;
		  }
/*		  field = document.getElementById("is_virtual"+i); /* due to the double fieldname trick you cannot use Mainform.is_virtual here */
/*		  if(is_virtual != "")
		  { if(field.checked && (is_virtual == "0"))
			{ field.checked = false; changed = true; }
		    if(!(field.checked) && (is_virtual == "1"))
			{ field.checked = true; changed = true; }
		  }	
*/		  field = eval("document.Mainform.nb_days_accessible"+i);
		  if(nb_days_accessible != "")
		  { if(nb_days_accessible != field.value) changed = true;
			field.value = nb_days_accessible;
		  }
		  field = eval("document.Mainform.nb_downloadable"+i);
		  if(nb_downloadable != "")
		  { if(nb_downloadable != field.value) changed = true;
			field.value = nb_downloadable;	
		  }
		}
		else if (fieldtext == "tags")
		  myval2 = myval.substring(0,32);
		else myval2 = myval;
		
		/* now implement the new values */
		if((fieldname == "VAT") || (fieldtext == "manufacturer"))
		{	oldvalue = field.selectedIndex;
			field.selectedIndex = myval2;
			if(oldvalue != myval2) changed = true;
		}
		else if((fieldtext == "active") || (fieldtext == "on_sale") || (fieldtext == "online_only") || (fieldtext=="show_condition") || (fieldtext=="ls_alert"))
		{   field = field[1];
			oldvalue = field.checked;
			field.checked = myval;
			if(oldvalue != myval) changed = true;
		}
		else if((fieldtext.match(/feature[0-9]*field/)) && (!multi_feature))
		{	/* note that replace has already been prepared above under "replace" */
	        let field2 = eval("document.Mainform."+fieldname+"_sel"+i);
			oldvalue = field.value;
			if(field2)
				oldvalue2 = field2.selectedIndex;
			if(custom == 0)
			{	if((action != "replace") || (field2.selectedIndex == oldval))
				{ field2.selectedIndex = myval;
				  field.value = "";
				}
			}
			else
			{	if(field2) // if there is select box
				  field2.selectedIndex = 0;
				field.value = myval2;
			}
			if((oldvalue != field.value) || (field2 && (oldvalue2 != field2.selectedIndex)))
				changed = true;
		}
		else if((fieldtext.match(/feature[0-9]*field/)) && (multi_feature))
		{ /* first look whether this value already exists */
		  /* with content=1 myval=text myval2=num */
	      var countfield = document.getElementsByName(fieldname+'_count'+i)[0];
          var fcount = parseInt(countfield.value);
		  if((action == "add") || (action == "remove") || (action == "set")) 
		  {  var searchterm=myval; var searchcustom = custom; }
		  else /* replace */
		  {  var searchterm=oldval; var searchcustom = oldcustom; }
		  var found = false;
	      for(var t=0; t<fcount; t++)
	      { if(searchcustom == 0) /* select: test id */
			{ var testval = eval("Mainform."+fieldname+t+'s'+i);
              if(!testval) continue; /* deal with deleted features */
	          if(testval.value == searchterm) { found = true; testpos=0; break; }
	        }
			else /* free text: test text */
			{ var testval = eval("Mainform."+fieldname+t+'t'+i);
              if(!testval) continue; /* deal with deleted features */
	          if(testval.value == searchterm) 
			  { found = true; 
		        var testpos = eval("Mainform."+fieldname+t+'s'+i+".value");
		        break; 
				}
			}
		  }
		  var contenttable = document.getElementById(fieldname+'content'+i);
		  if(action=="set")
		  { for(var t=0; t<fcount; t++)
	          contenttable.deleteRow(0);
		    if(((custom==0) && (myval==0)) || ((custom==1) && (myval==""))) /* if empty */
			{ countfield.value = 0;
			  if(fcount != 0) changed = true;
			  found = true;
			}
			else
			  found = false;	
			fcount = 0;
		  }
		  if((action == "add") || ((action == "set") && !found))
		  { if(found) continue;
	        var contentrow = contenttable.insertRow(-1);
	        if(custom == 0)
			  contentrow.innerHTML = '<td><b title="'+myval+'">'+myval3+'</b><input type="hidden" name="'+fieldname+fcount+'s'+i+'" value="'+myval+'"></td><td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"></a></td>';
			else
			  contentrow.innerHTML = '<td><input name="'+fieldname+fcount+'t'+i+'" value="'+myval+'"><input type="hidden" name="'+fieldname+fcount+'s'+i+'" value="0"></td><td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"></a></td>';
            countfield.value = fcount+1;
			changed = true;
		  }
		  else if(action == "remove")
		  { if(!found) continue;
	        var tr = testval.parentNode.parentNode;
            tr.innerHTML = "<td></td>";
			changed = true;
		  }
		  else if((action == "replace") && !massform.insider.checked)
		  { if(!found) continue;
	        found = false;
		    if(oldcustom != custom)
		    { for(var u=0; u<fcount; u++)
	          { if(custom == 0) /* select: test id */
			    { var testval2 = eval("Mainform."+fieldname+u+'s'+i);
                  if(!testval2) continue; /* deal with deleted features */
	              if(testval2.value == myval) { found = true; break; }
	            }
			    else /* free text: test text */
			    { var testval2 = eval("Mainform."+fieldname+u+'t'+i);
                  if(!testval2) continue; /* deal with deleted features */
	              if(testval2.value == myval) { found = true; break; }
				}
			  }
			}
	        var tr = testval.parentNode.parentNode;
			if(found)
			  tr.innerHTML = "<td></td>";
		    else if(custom == 0)
			  tr.innerHTML = '<td><b title="'+myval+'">'+myval3+'</b><input type="hidden" name="'+fieldname+t+'s'+i+'" value="'+myval+'"></td><td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"></a></td>';
			else
			  tr.innerHTML = '<td><input name="'+fieldname+t+'t'+i+'" value="'+myval+'"><input type="hidden" name="'+fieldname+t+'s'+i+'" value="'+testpos+'"></td><td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"></a></td>';
			changed = true;
		  }
		  else if((action == "replace") && massform.insider.checked)
		  { for(var t=0; t<fcount; t++)
	        { if((oldcustom == 0) || (custom == 0)) /* searchterm is select */
		        continue;
			  else /* free text: test text */
			  {  var testval = eval("Mainform."+fieldname+t+'t'+i);
                if(!testval) continue; /* deal with deleted features */
			    testval.value = testval.value.replace(oldval, myval);
			 }
		    }
		  }
		  else if(action == "copy from field")
		  { if(copyfield == "id_product")
			  myval2 = get_product_id(i);
		    else 
		    { myval2 = field.parentNode.parentNode.cells[cellindex].innerHTML;
		      myval2 = processcopyfield(copyfield, myval2, fieldtext)
		    }
			/* if there is an existing custom field we use that */
			var testval = false;
			if(!massform.newy.checked)
	  	    { for(var t=0; t<fcount; t++)
	          { testval = eval("Mainform."+fieldname+t+'t'+i);
                if(!testval) continue; /* deal with deleted features */
			    testval.value = myval2;
			    break;
			  }
		    }
			else
				var t=fcount;
			if(!testval) /* ... otherwise we create a new custom field */
			{ add_feature(i,fieldname,4);  /* add empty field */
			  testval = eval("Mainform."+fieldname+t+'t'+i);
			  testval.value = myval2;
			}
		    changed = true;
		  }
		}
		else if((action != "add") && (action != "remove") && (action != "set as default") && (action != "TinyMCE") 
			&& (action != "TinyMCE-deluxe") && (action != "add fixed target discount") && (fieldname != "image")
			&& (fieldname != "virtualp")&& (fieldname != "redirect_type"))
		{	oldvalue = field.value;
			field.value = myval2;
			if(oldvalue != myval2) changed = true;
		}

		if((fieldname == "price") && changed)
			price_change(field);
		else if((fieldname == "priceVAT") && changed)
			priceVAT_change(field);
		else if((fieldname == "VAT") && changed)
			VAT_change(field);
		else if(fieldname == "image")
		{ 
			
		}
		else if(fieldname == "stockflags") /* you cannot check here for "changed" as that would exclude a second mass edit with a different warehouse */
		{	if(changed)
			  stockflags_change(field);
			if(myval=="3")
			{ var warehouse = eval("document.Mainform.stockflags_warehouse"+i);
			  if(warehouse) /* warehouse is not present for products that are already ASM with Warehousing */
				warehouse.value = mywarehouse;
			}
		}
		if(((fieldname == "description") || (fieldtext == "description_short")) && (field.parentNode.childNodes[0].tagName == "DIV"))
			base.value = tinyMCE.get(fieldname+i).setContent(field.value);	
		if(changed) /* we flag only those really changed */
			reg_change(field);
	}
  }
  
  /* processcopyfield() is called from massUpdate to the source for copy/replace from field */
  /* copyfield is the source, fieldtext the target */
  function processcopyfield(copyfield, myval2, fieldtext)
  { 
	if(copyfield == "category")
	{ tmp = myval2.match(/<a title=\"([^\"]*)\" href=\"[^\"]*\" target=\"_blank\" tabindex=\"-1\">/);
	  myval2 = tmp[1];
	}	
	else if(copyfield == "id_category")
	{ tmp = myval2.match(/<a title=\"([^\"]*)\" href=\"[^\"]*\" target=\"_blank\" tabindex=\"-1\">([^<]*)</);
	  myval2 = tmp[2];
	}
	else if(copyfield.substring(0,4) == "tags")
	{ var regex = /(<([^>]+)>)/ig;
	  if(fieldtext.substring(0,4) == "tags")
	  { myval2 = myval2.replace(regex, ",");
		var regex = /,+/ig;
		myval2 = myval2.replace(regex, ",");
		myval2 = myval2.substring(1,myval2.length-1);
	  }
	  else
	  { myval2 = myval2.replace(regex, " ");
		var regex = / +/ig;
		myval2 = myval2.replace(regex, " ");
	  }
	  myval2 = myval2.trim();
	}
	else
		myval2 = striptags(myval2);

    if(((fieldtext == "price") || (fieldtext == "priceVAT") || (fieldtext == "unitPRatio") || (fieldtext == "wholesaleprice")) && !isNumber(myval2)) 
    { alert("Only numeric prices are allowed!\nUse decimal points!"); 
	return;
    }
    if(copyfield == "name")
	  myval2 = myval2.replace(/<[^>]*>/gm, "");
    if((fieldtext == "meta_description") || (fieldtext == "meta_title"))
	  myval2 = myval2.replace(/<(?:.|\n)*?>/gm, "");
	else if(fieldtext.match(/feature[0-9]*field/))
	{ myval2 = myval2.replace(/<\/?b>/gm, ""); 
	}
	return myval2;
  }
  
  function changeMfield()  /* change input fields for mass update when field is selected */
  { var myarr;
    base = eval("document.massform.field");
	fieldtext = base.options[base.selectedIndex].value;
	if(myarray[fieldtext]) /* the myarray matrix lists which functions are allowed for a field */
	  myarr = myarray[fieldtext];
	else
	  myarr = myarray["default"];
	var muspan = document.getElementById("muval");
	for(var i=0; i<myarr.length; i++) /* use here .length to prepare for extra elements */
	{	if((i==20)&&(fieldtext.substring((fieldtext.length-3),(fieldtext.length-2)) == '_')) 
		{ /* hide translate for extra languages */
		  document.massform.action.options[i+1].style.display = "none";
		  document.massform.action.options[i+1].disabled = true;
		  continue;
		}
	    //alert(fieldtext.substring((fieldtext.length-3),(fieldtext.length-2)));
		if(myarr[i] == 0)
		{	document.massform.action.options[i+1].style.display = "none";
			document.massform.action.options[i+1].disabled = true;
		}
		else
		{	document.massform.action.options[i+1].style.display = "block";
			document.massform.action.options[i+1].disabled = false;
		}
	}
	document.massform.action.selectedIndex = 0;

	if(fieldtext == "aDeliveryT") muspan.innerHTML = "<select name=\"myvalue\">"+deliverytimesblock;
	else if(fieldtext == "attachmnts") muspan.innerHTML = "<select name=\"myvalue\">"+attachmentblock1;
	else if(fieldtext == "availorder") muspan.innerHTML = "<select name=\"myvalue\">"+availorderblock;
	else if(fieldtext == "carrier") muspan.innerHTML = "<select name=\"myvalue\">"+carrierblock1;	
	else if(fieldtext == "category") muspan.innerHTML = "<select name=\"myvalue\">"+categoryblock1;	
	else if(fieldtext == "condition") muspan.innerHTML = "<select name=\"myvalue\">"+conditionblock;
	else if(fieldtext == "manufacturer") muspan.innerHTML = "<select name=\"myvalue\">"+manufacturerblock;
	else if(fieldtext == "out_of_stock") muspan.innerHTML = "<select name=\"myvalue\">"+out_of_stockblock;		
	else if(fieldtext == "pack_stock_type") muspan.innerHTML = "<select name=\"myvalue\">"+pack_stock_typeblock;
	else if(fieldtext == "shopz") 
	{ var shopz = shop_ids.split(",");
	  let tmp = " shop nr. <select name=\"myvalue\">";
	  for(var i=0; i<shopz.length; i++)
	  { tmp += "<option>"+shopz[i]+"</option>";
	  }
	  muspan.innerHTML = tmp+"</select>";
	}
	else if(fieldtext == "stockflags") muspan.innerHTML = "<select name=\"myvalue\" onchange=\"stockflags_mass_change(this)\"><option value=\"1\">Manual</option><option value=\"2\">Adv Stock Management</option><option value=\"3\">ASM with Warehousing</option></select>"+
		" <div id=stockspan style=\"display:none\">Move stock of shop(s) to warehouse: <select name=stockflags_warehouse>"+warehouseblock+"</div>";
	else if(fieldtext == "supplier") muspan.innerHTML = "<div style='display:inline-block; vertical-align:top; margin-top: 2px;'><select name=\"myvalue_sel\">"+supplierblock1+'<br>\"Set\" and \"increase\" apply to the price</div>';	
	else if(fieldtext == "VAT")  muspan.innerHTML = "<select name=\"myvalue\">"+taxblock; 
	else if(fieldtext == "visibility") muspan.innerHTML = "<select name=\"myvalue\">"+visibilityblock;
 	else muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\"></textarea>";
  }
	  
/* this slightly modified function comes from admin.js in PS 1.6.11 */
function str2url(str)
{   str = str.toUpperCase();
	str = str.toLowerCase();
	if (parseInt(allow_accented_chars))
		str = str.replace(/[^a-z0-9\s\'\:\/\[\]-]\\u00A1-\\uFFFF/g,'');
	else
	{ 
		/* Lowercase */
		str = str.replace(/[\u00E0\u00E1\u00E2\u00E3\u00E5\u0101\u0103\u0105\u0430]/g, 'a');
        str = str.replace(/[\u0431]/g, 'b');
		str = str.replace(/[\u00E7\u0107\u0109\u010D\u0446]/g, 'c');
		str = str.replace(/[\u010F\u0111\u0434]/g, 'd');
		str = str.replace(/[\u00E8\u00E9\u00EA\u00EB\u0113\u0115\u0117\u0119\u011B\u0435\u044D]/g, 'e');
        str = str.replace(/[\u0444]/g, 'f');
		str = str.replace(/[\u011F\u0121\u0123\u0433\u0491]/g, 'g');
		str = str.replace(/[\u0125\u0127]/g, 'h');
		str = str.replace(/[\u00EC\u00ED\u00EE\u00EF\u0129\u012B\u012D\u012F\u0131\u0438\u0456]/g, 'i');
		str = str.replace(/[\u0135\u0439]/g, 'j');
		str = str.replace(/[\u0137\u0138\u043A]/g, 'k');
		str = str.replace(/[\u013A\u013C\u013E\u0140\u0142\u043B]/g, 'l');
        str = str.replace(/[\u043C]/g, 'm');
		str = str.replace(/[\u00F1\u0144\u0146\u0148\u0149\u014B\u043D]/g, 'n');
		str = str.replace(/[\u00F2\u00F3\u00F4\u00F5\u00F8\u014D\u014F\u0151\u043E]/g, 'o');
        str = str.replace(/[\u043F]/g, 'p');
		str = str.replace(/[\u0155\u0157\u0159\u0440]/g, 'r');
		str = str.replace(/[\u015B\u015D\u015F\u0161\u0441]/g, 's');
		str = str.replace(/[\u00DF]/g, 'ss');
		str = str.replace(/[\u0163\u0165\u0167\u0442]/g, 't');
		str = str.replace(/[\u00F9\u00FA\u00FB\u0169\u016B\u016D\u016F\u0171\u0173\u0443]/g, 'u');
        str = str.replace(/[\u0432]/g, 'v');
		str = str.replace(/[\u0175]/g, 'w');
		str = str.replace(/[\u00FF\u0177\u00FD\u044B]/g, 'y');
		str = str.replace(/[\u017A\u017C\u017E\u0437]/g, 'z');
		str = str.replace(/[\u00E4\u00E6]/g, 'ae');
        str = str.replace(/[\u0447]/g, 'ch');
        str = str.replace(/[\u0445]/g, 'kh');
		str = str.replace(/[\u0153\u00F6]/g, 'oe');
		str = str.replace(/[\u00FC]/g, 'ue');
        str = str.replace(/[\u0448]/g, 'sh');
        str = str.replace(/[\u0449]/g, 'ssh');
        str = str.replace(/[\u044F]/g, 'ya');
        str = str.replace(/[\u0454]/g, 'ye');
        str = str.replace(/[\u0457]/g, 'yi');
        str = str.replace(/[\u0451]/g, 'yo');
        str = str.replace(/[\u044E]/g, 'yu');
        str = str.replace(/[\u0436]/g, 'zh');

		/* Uppercase */
		str = str.replace(/[\u0100\u0102\u0104\u00C0\u00C1\u00C2\u00C3\u00C4\u00C5\u0410]/g, 'A');
        str = str.replace(/[\u0411]/g, 'B');
		str = str.replace(/[\u00C7\u0106\u0108\u010A\u010C\u0426]/g, 'C');
		str = str.replace(/[\u010E\u0110\u0414]/g, 'D');
		str = str.replace(/[\u00C8\u00C9\u00CA\u00CB\u0112\u0114\u0116\u0118\u011A\u0415\u042D]/g, 'E');
        str = str.replace(/[\u0424]/g, 'F');
		str = str.replace(/[\u011C\u011E\u0120\u0122\u0413\u0490]/g, 'G');
		str = str.replace(/[\u0124\u0126]/g, 'H');
		str = str.replace(/[\u0128\u012A\u012C\u012E\u0130\u0418\u0406]/g, 'I');
		str = str.replace(/[\u0134\u0419]/g, 'J');
		str = str.replace(/[\u0136\u041A]/g, 'K');
		str = str.replace(/[\u0139\u013B\u013D\u0139\u0141\u041B]/g, 'L');
        str = str.replace(/[\u041C]/g, 'M');
		str = str.replace(/[\u00D1\u0143\u0145\u0147\u014A\u041D]/g, 'N');
		str = str.replace(/[\u00D3\u014C\u014E\u0150\u041E]/g, 'O');
        str = str.replace(/[\u041F]/g, 'P');
		str = str.replace(/[\u0154\u0156\u0158\u0420]/g, 'R');
		str = str.replace(/[\u015A\u015C\u015E\u0160\u0421]/g, 'S');
		str = str.replace(/[\u0162\u0164\u0166\u0422]/g, 'T');
		str = str.replace(/[\u00D9\u00DA\u00DB\u0168\u016A\u016C\u016E\u0170\u0172\u0423]/g, 'U');
        str = str.replace(/[\u0412]/g, 'V');
		str = str.replace(/[\u0174]/g, 'W');
		str = str.replace(/[\u0176\u042B]/g, 'Y');
		str = str.replace(/[\u0179\u017B\u017D\u0417]/g, 'Z');
		str = str.replace(/[\u00C4\u00C6]/g, 'AE');
        str = str.replace(/[\u0427]/g, 'CH');
        str = str.replace(/[\u0425]/g, 'KH');
		str = str.replace(/[\u0152\u00D6]/g, 'OE');
		str = str.replace(/[\u00DC]/g, 'UE');
        str = str.replace(/[\u0428]/g, 'SH');
        str = str.replace(/[\u0429]/g, 'SHH');
        str = str.replace(/[\u042F]/g, 'YA');
        str = str.replace(/[\u0404]/g, 'YE');
        str = str.replace(/[\u0407]/g, 'YI');
        str = str.replace(/[\u0401]/g, 'YO');
        str = str.replace(/[\u042E]/g, 'YU');
        str = str.replace(/[\u0416]/g, 'ZH');

		str = str.toLowerCase();

		str = str.replace(/[^a-z0-9\s\'\:\/\[\]-]/g,'');
	}
	str = str.replace(/[\u0028\u0029\u0021\u003F\u002E\u0026\u005E\u007E\u002B\u002A\u002F\u003A\u003B\u003C\u003D\u003E]/g, '');
	str = str.replace(/[\s\'\:\/\[\]-]+/g, ' ');

	// Add special char not used for url rewrite
	str = str.replace(/[ ]/g, '-');
	str = str.replace(/[\/\\"'|,;%]*/g, '');

	str = str.replace(/-$/,""); /* added */

	return str;
}

/* when Mass form Action field changed: */
	function changeMAfield()
	{ var tmp;
	  var base = eval("document.massform.action");
	  var action = base.options[base.selectedIndex].text;
	  base = eval("document.massform.field");
	  var fieldname = base.options[base.selectedIndex].value;
	  var muspan = document.getElementById("muval");
	  if(((fieldname=="active") || (fieldname=="on_sale") || (fieldname=="online_only") || (fieldname=="show_condition") || (fieldname=="ls_alert")) && (action=="set"))
		muspan.innerHTML = "value: <input type=\"checkbox\" name=\"myvalue\">";
	  else if ((action == "copy from field") || (action == "replace from field"))
	  { tmp = document.massform.field.innerHTML;
		var fieldtext = base.options[base.selectedIndex].text;
		tmp = tmp.replace("<option value=\"Select a field\">Select a field</option>","<option value=\"Select field to copy from\">Select field to copy from</option><option value=\"id_product\">id_product</option>");
 	    tmp = tmp.replace("<option value=\""+fieldname+"\">"+fieldtext+"</option>","");
		tmp = tmp.replace("<option value=\"active\">active</option>","");
		tmp = tmp.replace("<option value=\"category\">category</option>","<option value=\"category\">main category</option><option value=\"id_category\">main id_category</option>");
		tmp = tmp.replace("<option value=\"image\">image</option>","");
		tmp = tmp.replace("<option value=\"accessories\">accessories</option>","");
		tmp = tmp.replace("<option value=\"combinations\">combinations</option>","");
		tmp = tmp.replace("<option value=\"discount\">discount</option>","");
		tmp = tmp.replace("<option value=\"carrier\">carrier</option>","");
		if (action == "copy from field")
	    { muspan.innerHTML = "<select name=copyfield>"+tmp+"</select>";
		  if((fieldname.match(/feature[0-9]*field/)) && (multi_feature))
			  muspan.innerHTML += ' <input type=checkbox name=newy> new entry';
		}
		else /* replace from field */
			muspan.innerHTML = "text to replace <textarea name=\"oldval\" class=\"masstarea\"></textarea> <select name=copyfield>"+tmp+"</select> regexp <input type=checkbox name=myregexp>";
		if(fieldname == "image")
			muspan.innerHTML += " &nbsp; covers only <input type=radio name=coverage value=cover> &nbsp; <input type=radio name=coverage value=all checked> all images &nbsp; &nbsp; <input type=checkbox name=emptyonly> Empty legends only";
	  }
	  else if ((action=="set") && (fieldname=="image"))
	  { muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\"></textarea> &nbsp; covers only <input type=radio name=coverage value=cover> &nbsp; <input type=radio name=coverage value=all checked> all images &nbsp; &nbsp; <input type=checkbox name=emptyonly> Empty legends only";
	  }
	  else if ((action=="remove") && (fieldname=="image"))
	  { muspan.innerHTML = "<input type=checkbox name='covers'>covers &nbsp; <input type=checkbox name='others'>others &nbsp; Containing following text in legend <input size=5 name=legtext>";
	  }
	  else if (action=="translate")
	  { if(translateflag == 0)
			muspan.innerHTML = "You need to buy the Prestools translate plugin and acquire a Deepl account to use this function! This plugin is not part of the super package.";
		else if(translateflag == 1)
			muspan.innerHTML = "You need to configure your Prestools translate plugin by adding the user name and password of your Deepl account at the top of the TE_plugin_translate.php file.";
		else
		{	
			tmp = "<span id='transspan'>Translate from <select name='oldlang' width=20><option value=0>Select a language</option>"+deeplorigs+"</select>";
			tmp += " to <select name='newlang' width=20><option value=0>Select a language</option>"+deepltargets+"</select></span>";
			if(Mainform.extralangfields.value.length > 0)
				tmp += ' <input type=checkbox id="transposer" onclick="settransposemode(this)"> Use transpose mode';
			tmp += "<br>This will translate all editable text fields! It doesn't work with TinyMCE.";
			muspan.innerHTML = tmp;
		}
	  }
	  else if((fieldname.match(/feature[0-9]*field/)) && (multi_feature))
	  { var fld = fieldname.substr(7).replace("field","");
		var idx = featurekeys.indexOf(fld);
		if(featureblocks[idx] == "")  /* if no predefined values */
		{ if ((action == "add") || (action == "remove"))
			   muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea>"; 
		  else if (action == "replace") 
			   muspan.innerHTML = "old: <textarea name=\"oldval\" class=\"masstarea\"></textarea> new: <textarea name=\"myvalue\" class=\"masstarea\"></textarea> regexp <input type=checkbox name=myregexp> &nbsp; replace inside text <input type=checkbox name=insider>";
		  else if (action == "set") 
			   muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea>"
					+" All existing values will be erased.";
		}
		else
	  	{ if ((action == "add") || (action == "remove"))
				muspan.innerHTML = "<select name=\"myvalue_sel\" onchange=\"feature_change(this)\">"+featureblocks[idx]+"<textarea name=\"myvalue\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea>"; 
		  else if (action == "replace") /* replace */
			    muspan.innerHTML = "old: <select name=\"oldval_sel\" onchange=\"feature_change(this)\">"+featureblocks[idx]+"<textarea name=\"oldval\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea>"
				+"new: <select name=\"myvalue_sel\" onchange=\"feature_change(this)\">"+featureblocks[idx]+"<textarea name=\"myvalue\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea> regexp <input type=checkbox name=myregexp> &nbsp; replace inside text <input type=checkbox name=insider>		<br>Replace inside text will only work when no select boxes are used.";
		  else if (action == "set") 
				muspan.innerHTML = "<select name=\"myvalue_sel\" onchange=\"feature_change(this)\">"+featureblocks[idx]+"<textarea name=\"myvalue\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea>"
					+" All existing values will be erased.";
		}
	  }
	  else if((fieldname.match(/feature[0-9]*field/)) && (!multi_feature))
	  { var fld = fieldname.substr(7).replace("field","");
		var idx = featurekeys.indexOf(fld);
		if(featureblocks[idx] == "")
		{ if (action == "set")
			   muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea>"; 
		  else /* replace */
			   muspan.innerHTML = "old: <textarea name=\"oldval\" class=\"masstarea\"></textarea> new: <textarea name=\"myvalue\" class=\"masstarea\"></textarea> regexp <input type=checkbox name=myregexp>";
		}
		else
	  	{ if (action == "set")
				muspan.innerHTML = "<select name=\"myvalue_sel\" onchange=\"feature_change(this)\">"+featureblocks[idx]+"<textarea name=\"myvalue\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea>"; 
		  else /* replace */
			    muspan.innerHTML = "old: <select name=\"oldval_sel\" onchange=\"feature_change(this)\">"+featureblocks[idx]+"<textarea name=\"oldval\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea>"
					+"new: <select name=\"myvalue_sel\" onchange=\"feature_change(this)\">"+featureblocks[idx]+"<textarea name=\"myvalue\" class=\"masstarea\" onkeyup=\"feature_change_event(event)\"></textarea> regexp <input type=checkbox name=myregexp>";
		}
	  }
	  else if(fieldname=="supplier")
	  { if((action=="set") || (action=="increase%") || (action=="increase amount"))
	    { tmp = "<select name=\"myvalue_sel\"><option value=0>All suppliers</option>"+supplierblock1;
		  tmp += " &nbsp; value: <input name=\"myvalue\">";
		  muspan.innerHTML = tmp;
		}
		else
		{ muspan.innerHTML = "<select name=\"myvalue_sel\">"+supplierblock1;	
		}
	  }
	  else if(fieldname=="redirect")
	  { tmp = "<div style='display:inline-block; vertical-align:middle'>";
		tmp += "Redirect type: <select name=\"myvalue_sel\">"+redirectblock;
		tmp += "<br>Target id: <input name=\"myvalue\" size=3>";
		tmp += "<br>When empty the target field will be ignored</div>";
		muspan.innerHTML = tmp;
	  }
	  
	  else if(fieldname=="customizations")
	  { if(action=="add")
		{ tmp = ' Values: &nbsp; <table style="display:inline; vertical-align:middle"><tr><td>';
		  var langids = lang_ids.split(',');
		  var langcodes = lang_codes.split(',');
		  var len = langids.length;
		  for(var i=0; i<len; i++)
		  { tmp += '<span style="display: inline-block; width:16px">'+langcodes[i]+'</span><textarea name="custom_name'+langids[i]+'" rows="1" cols="30"></textarea><br>';
		  }
		  tmp += '</td><td><select name="custom_type"><option>textfield</option><option>uploadfile</option></select></td><td><input name="custom_req" type="checkbox">req</td></tr></table>';
		  muspan.innerHTML = tmp;
		}
		else if(action=="remove")
		{ tmp = "Name in active language: <textarea name=\"myvalue\" class=\"masstarea\" ></textarea>";
		  muspan.innerHTML = tmp;
		}
		else if(action=="replace")
		{ tmp = 'Name in active language :<textarea name=\"myvalue\" class=\"masstarea\" ></textarea>';
		  tmp += ' &nbsp;New values: &nbsp; <table style="display:inline; vertical-align:middle"><tr><td>';
		  var langids = lang_ids.split(',');
		  var langcodes = lang_codes.split(',');
		  var len = langids.length;
		  for(var i=0; i<len; i++)
		  { tmp += '<span style="display: inline-block; width:16px">'+langcodes[i]+'</span><textarea name="custom_name'+langids[i]+'" rows="1" cols="30"></textarea><br>';
		  }
		  tmp += '</td><td><select name="custom_type"><option>textfield</option><option>uploadfile</option></select></td><td><input name="custom_req" type="checkbox">req</td></tr></table>';
		  muspan.innerHTML = tmp;
		}
	  }	  
	  else if(action == "round")
	  { muspan.innerHTML = "level: <select name=\"myvalue\"><option value=\"2\">xxxx.xx0</option><option value=\"1\">xxxx.x0</option><option value=\"0\">xxxx.00</option><option value=\"-1\">xxx0.x0</option><option value=\"-2\">xx00.00</option><option value=\"-3\">x000.00</option></select>";
	    muspan.innerHTML += " &nbsp; round 5 <select name=\"massupper\"><option>up</option><option>down</option></select>";
	  }
	  else if (action == "balance html") muspan.innerHTML = "<input name=\"myvalue\" type=hidden value=0>Make textfield html compliant. Open divs and spans will be closed.";
	  else if (action == "touch") muspan.innerHTML = "<input name=\"myvalue\" type=hidden value=0>Mark every row changed. Sometimes useful when the database is dirty.";
	  else if (action == "replace") muspan.innerHTML = "old: <textarea name=\"oldval\" class=\"masstarea\"></textarea> new: <textarea name=\"myvalue\" class=\"masstarea\"></textarea> regexp <input type=checkbox name=myregexp>";
	  else if (action == "increase%") muspan.innerHTML = "Percentage (can be negative): <input name=\"myvalue\">";
	  else if (action == "increase amount") muspan.innerHTML = "Amount (can be negative): <input name=\"myvalue\">";
	  else if (action == "copy from other lang") muspan.innerHTML = "Select language to copy from: <select name=copylang>"+langcopyselblock+"</select>. This affects name, description and meta fields.";
	  else if((action=="TinyMCE") || (action=="TinyMCE-deluxe") || (action=="strip html"))
	    muspan.innerHTML = "";
	  else if ((fieldname=="discount") &&(action=="add"))
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
	  { tmp = "<br/>";
	    tmp += "<select name=shop style=\"width:100px\"><option value=0>All shops</option>"+shopblock.replace(" selected","")+"</select>";
		tmp += " &nbsp; ";
	    tmp += "<select name=currency><option value=0>All cs</option>"+currencyblock+"</select>";
	    tmp += " &nbsp; <select name=country style=\"width:100px\"><option value=0>All countries</option>"+countryblock+"</select>";
		tmp += " &nbsp; <select name=group style=\"width:90px\"><option value=0>All groups</option>"+groupblock+"</select>";
		tmp += " &nbsp; Cust.id<input name=customer style=\"width:30px\">";
		tmp += " &nbsp; FromPrice<input name=price style=\"width:50px\">";
		tmp += " &nbsp; Min.Qu.<input name=quantity style=\"width:20px\" value=\"1\">";
		tmp += " &nbsp; discount<input name=reduction style=\"width:50px\">";
		if (prestashop_version >= "1.6.0.11")
			tmp += "<select name=reductiontax><option value=0>excl tax</option><option value=1 selected>incl tax</option></select>";
		else
			tmp += "<select name=reductiontax><option value=1 selected>incl tax</option></select>";
		tmp += " &nbsp; <select name=reductiontype><option>amt</option><option>pct</option></select>";
		tmp += " &nbsp;period:<input name=datefrom style=\"width:70px\">";
		tmp += "-<input name=dateto style=\"width:70px\"> (yyyy-mm-dd)";
		tmp += "<br/>";
	    muspan.innerHTML = tmp;
	  }
	  else if ((fieldname=="discount") &&(action=="add fixed target discount"))
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
	  { tmp = "<select name=shop style=\"width:100px\"><option value=0>All shops</option>"+shopblock.replace(" selected","")+"</select>";
		tmp += "<select name=currency><option value=0>All cs</option>"+currencyblock+"</select>";
	    tmp += " &nbsp; <select name=country style=\"width:100px\"><option value=0>All countries</option>"+countryblock+"</select>";
		tmp += " &nbsp; <select name=group style=\"width:90px\"><option value=0>All groups</option>"+groupblock+"</select>";
		tmp += " &nbsp; Cust.id<input name=customer style=\"width:30px\">";
		tmp += " &nbsp; Min.Qu.<input name=quantity style=\"width:20px\" value=\"1\">";
		tmp += " &nbsp;period:<input name=datefrom style=\"width:70px\">";
		tmp += "-<input name=dateto style=\"width:70px\"> (yyyy-mm-dd)";
		tmp += " &nbsp; Target price: <input name=targetprice style=\"width:30px\" onkeyup=\"fixed_target_discount_change(this)\"> Excl VAT";
		tmp += " &nbsp; <input name=targetpriceVAT style=\"width:30px\" onkeyup=\"fixed_target_discount_change(this)\"> Incl VAT<br/>";
		tmp += "This specialized function creates discounts with the same outcome.<br/>If you";
	    tmp += " set a target of 10 and a product costs 12 it gets a discount of 2. If it"; 
		tmp += " costs 40 its discount will be 30. If its price is below 10 no discount is added.<br/>";
	    muspan.innerHTML = tmp;
	  }	  
	  else if ((fieldname=="discount") &&(action=="remove"))
	  { tmp = " &nbsp; where &nbsp; ";
	    tmp += "<select name=fieldname style=\"width:150px\" onchange=\"dc_field_optioner()\"><option>Select subfield</option><option>shop</option><option>currency</option><option>country</option><option>group</option><option>customer</option>";
	    tmp += "<option>price</option><option>quantity</option><option>reduction</option><option>reductiontype</option><option value='from'>date_from</option><option value='to'>date_to</option></select>";
		tmp += "<span id=\"dc_options\">";
	    muspan.innerHTML = tmp;
	  }
	  else if ((fieldname=="price") && (action=="recalculate excl price"))
	  { muspan.innerHTML = ' Previous VAT <input name=myvalue size=3><br>After you changed the VAT rate you can use this function to adapt the price excl VAT so that the price with VAT will stay the same.';
	  }
	  else if (fieldname=="virtualp")
	  { tmp = " &nbsp; ";
		//tmp += 'is_virtual <input size=1 name="is_virtual"> &nbsp; ';
	    tmp += 'Exp date <input name="date_expiration" > &nbsp; Days <input name="nb_days_accessible" style="width:45px;">';
		tmp += ' &nbsp; Downloads <input name="nb_downloadable" style="width:45px;">';
		tmp += '<br>Only filled fields will be updated!';
	    muspan.innerHTML = tmp;
	  }
	  else if ((fieldname=="accessories") &&((action=="add")||(action=="remove")))
		muspan.innerHTML = "value: <input name=\"myvalue\">";
	  else if (document.massform.action.options[3].style.display == "block")
		muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\"></textarea>";
	  else if (((fieldname=="price") || (fieldname=="priceVAT") || (fieldname=="qty")) &&(action=="set"))
		muspan.innerHTML = "value: <input name=\"myvalue\">";
	}
	
	/* for massedit discount remove: gives subfield options */
	function dc_field_optioner()
	{ var base = eval("document.massform.fieldname");
	  var fieldname = base.options[base.selectedIndex].text;
	  var tmp = "";
	  if (fieldname == "shop") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>All shops</option>"+shopblock+"</select>";
	  else if (fieldname == "currency") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>All currencies</option>"+currencyblock+"</select>";	
	  else if (fieldname == "country") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>All countries</option>"+countryblock+"</select>";
	  else if (fieldname == "group") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>All groups</option>"+groupblock+"</select>";	
	  else if (fieldname == "reductiontype") 
	    tmp = "<select name=subfield style=\"width:100px\"><option>amt</option><option>pct</option></select>";		
	  else 
	    tmp = "<input name=subfield size=40>";
	  var fld = document.getElementById("dc_options");
	  fld.innerHTML = " = "+tmp;
	}
	  
	function salesdetails(product)
	{ window.open("product-sales.php?id_product="+product+"&startdate="+startdate+"&enddate="+enddate+"&id_shop[]="+id_shop,"", "resizable,scrollbars,location,menubar,status,toolbar");
      return false;
    }

function fixed_target_discount_change(elt)
{ if(elt.name == "targetprice")
    massform.targetpriceVAT.value = "";
  else
    massform.targetprice.value = "";    	  
}

function sortTheTable(tab,col,flag) 
{ if(tabchanged != 0)
  { alert('You can only sort the table if it hasn\'t been changed yet!');
    /* copying fields will not copy changed contents!!! */
    return flag;
  }
  return sortTable(tab, col, flag);
}

/* getpath() takes a string like '189' and returns something like '/1/8/9' */
function getpath(name)
{ var str = '';
  for (var i=0; i<name.length; i++)
  { str += '/'+name[i];
  }
  return str;
} 

function change_allshops(flag)
{ if(flag == '1')
	document.body.style.backgroundColor = '#ff7';
  else if(flag == '2')
	document.body.style.backgroundColor = '#fc1';
  else
	document.body.style.backgroundColor = '#fff';
}

/* image only active checkbox change */
function ioa_change(elt)
{ elt.style.outline = "4px solid #ffa500"; /* create orange border */
}

/* this function adds a row with five search units to the search block */
function addselectrow(elt)
{ var row = document.getElementById('selextra');
  if(row.innerHTML != "") return;
  var box = document.getElementById('selbox3');
  var tmp = box.innerHTML;
  var tm2 = '<table ><tr>';
  for(var i=4; i<9; i++)
  { tm2 += '<td>'+tmp.replace(/3/g,i)+'</td>';
  }
  tm2 += '</tr></table>';
  row.innerHTML = tm2;
  elt.outerHTML = "";
}

function setStocker()
{ search_form.conda_fld1.value='s.quantity';
  search_form.cond_cmp1.value='lte';
  search_form.condb_fld1.value='ps.low_stock_threshold';
}

/* this function adds a row with a field comparison condition */
function addconditionrow(elt)
{ var tab = document.getElementById('condertable');
  tab.style.display='inline';
  elt.style.display='none';
}

function check_description(field, content, maxlength, line)
{ if((content.length > maxlength) && (maxlength > 0))
  { var linetext = "";
	if(line >= 0)
	  linetext = ' on line '+line+' for product '+get_product_id(line)+'. See mass edit function.';
    if(field == "description_short")
	  return confirm("The description_short field is too long. The length is "+content.length+". The maximum allowed length is "+psdlimit+linetext+". This can cause problems. If you continue there will be no further warnings. Are you sure you want to continue?");
	else
	  return confirm("The description field is too long. The length is "+content.length+". The maximum allowed length is 21844"+linetext+". This can cause problems. If you continue there will be no further warnings. Are you sure you want to continue?");
  }
  return -1;
}

function SubmitForm()
{ var divje;
  var reccount = numrecs3;
//baseName = window.location.pathname.substring(window.location.pathname.lastIndexOf('/')+1) om return adres door te geven
  var submitted=0;
  if(typeof imgonly !== 'undefined')
  { if(typeof imgoactiv === 'undefined') return false;
    for(var i=0; i<reccount; i++)
	{ var fld = eval("document.Mainform.active"+i+"[1]");
	  if(fld.style.outlineWidth != "4px") 
	  { fld.parentNode.parentNode.innerHTML = ""; 
      }
	}
  }
  else  /* regular page submit */
  { if(uploadingImages.size != 0)
	{ alert("You cannot submit while images are still uploading!");
	  return;
	}
	var descriptcheck = -1;
    for(var i=0; i<reccount; i++)
    { divje = document.getElementById('trid'+i); /* check for lines that we clicked away */
      if(!divje)
        continue;
	  var chg = divje.getAttribute('changed');
      if(chg == 0)
      { divje.parentNode.innerHTML='';
        continue;
      }

	  var docfield = eval("document.Mainform.description"+i);  /* handle TinyMCE */
	  if(docfield)
	  { if (docfield.parentNode.childNodes[0].tagName == "DIV")
	    { tmp = docfield.value.trim();
		  if(descriptcheck == -1)
		  { descriptcheck = check_description("description", tmp, 21844, i);
			if(!descriptcheck) return;
		  }
		  tinymce.remove("#description"+i);
		  docfield.value = tidy_html(docfield.value).trim();
	    }
	    else
	    { tmp = tidy_html(docfield.value);
		  if(descriptcheck == -1)
		  { descriptcheck = check_description("description", tmp, 21844, i);
			if(!descriptcheck) return;
		  }
		  docfield.value = tmp;
		}
	  }
	
	  docfield = eval("document.Mainform.description_short"+i);  /* handle TinyMCE */
	  if(docfield)
	  { if (docfield.parentNode.childNodes[0].tagName == "DIV") 
	    { tmp = docfield.value.trim();
		  if(descriptcheck == -1)
		  { descriptcheck = check_description("description_short", tmp, psdlimit, i);
			if(!descriptcheck) return;
		  }
		  tinymce.remove("#description_short"+i);
		  docfield.value = tidy_html(docfield.value).trim();
	    }
	    else
	    { tmp = tidy_html(docfield.value);
		  if(descriptcheck == -1)
		  { descriptcheck = check_description("description_short", tmp, psdlimit, i);
			if(!descriptcheck) return;
		  }
		  docfield.value = tmp;
		}
	  }

	  var carrier = eval("document.Mainform.carriersel"+i);
	  if(carrier)			/* note that this  will cause the carrier to be empty rather than none when there is an error message */
	  { if (carrier.options[0].value == "none")
	    { carrier.options.length=0;
	    }
	  }
	  
	  if(Mainform.selectmodestatus.value == 1) /* do not submit unchecked rows in checkbox mode */
	  { var fld = eval("Mainform.sel"+i);
		  if(!fld.checked)
			  chg=0;
		  else
			  fld.name = ""; /* prevent the fld variable from being sent with the form */
	  }
	
      if(chg == 0)
      { divje.parentNode.innerHTML='';
      }
	  else
	  { if((fields.indexOf("discount") !== -1) && (!check_discounts(i))) return false;
	    if(!check_shopz(i)) return false; /* check that at least one shop is selected */
		submitted++;
	  }
    }
    var tmp = document.getElementById('featureblock0');
    if(tmp.style.display == 'none')
      Mainform.featuresset.value = 0;
    else
      Mainform.featuresset.value = 1;  
  }
  
  /* Note: there is a more elegant solution with Jquery's serialize where you compact all variables */
  var sels = Mainform.getElementsByTagName('select').length;
  var inps = Mainform.getElementsByTagName('input').length;
  var txas = Mainform.getElementsByTagName('textarea').length;
  var tots = sels+inps+txas;

  if(tots > max_input_vars)
  { alert("You are trying to submit "+tots+" fields where your server's max_input_vars allows only "+max_input_vars+"!\nPlease reduce your number of fields.");
	return false;
  }
  
  Mainform.verbose.value = SwitchForm.verbose.checked;
  Mainform.skipindexation.value = IndexForm.skipindexation.checked;
  Mainform.urlsrc.value = location.href;
  Mainform.action = 'product-proc.php?c='+reccount+'&d='+submitted;
  Mainform.submit();
}