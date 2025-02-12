//-----------------------------------------------------------------------------
// sortTable(id, col, rev)
//
//  id  - ID of the TABLE, TBODY, THEAD or TFOOT element to be sorted.
//  col - Index of the column to sort, 0 = first column, 1 = second column,
//        etc.
//  rev - 0 = use normal order, except when the column is clicked a second time
//				1 = sort always up. 2 = sort always down.
//
// Make sure that there is no space between the </tr> and <tr>: spaces will count as extra childnodes and destroy the sort
//
// Note: the team name column (index 1) is used as a secondary sort column and
// always sorted in ascending order.
// alternative: datatables.net?
//-----------------------------------------------------------------------------
var reversesort = false;
var namecol = -1;
function mysort(aa, bb){
  a = aa[0];
  b = bb[0];
  if(reversesort == 1)  
  { if(a > b)
     return -1
   else if(a < b)
     return 1;
  }
  else
  { if(a > b)
     return 1
   else if(a < b)
     return -1;
  }
  a = aa[1];  /* index number: if two same fields: keep existing order */
  b = bb[1];
  if(a > b)
     return 1;
  else if(a < b)
     return -1;
  return 0; /* should never happen */
}

function mynumsort(aa, bb){
  a=aa[0]; b=bb[0];
  if(a=="") a = -1;
  else a = parseFloat(a);
  if(b=="") b = -1;
  else b = parseFloat(b);
  if(reversesort == 1) 
  { if(a > b)
     return -1;
   else if(a < b)
     return 1;
  }
  else
  { if(a > b)
     return 1;
   else if(a < b)
     return -1;
  }
  a = aa[1];  /* index number: if two same fields: keep existing order */
  b = bb[1];
  if(a > b)
     return 1;
  else if(a < b)
     return -1;
  return 0; /* should never happen */
}

function sortTable(id, col, rev) {
  
  // Get the table or table section to sort.
  var tblEl = document.getElementById(id);  /* note that we cannot hang the arrays on this element as we rewrite the array */
  var numrows = tblEl.rows.length;
  // the following function takes care that changed input values are implemented in the innerHTML. Without it the original values will be shown.
  // [].slice.call(tblEl.querySelectorAll('input[value]')).map(function(a){a.setAttribute('value', a.value);});
  var divEl = document.getElementById('testdiv');
  var cgEl = document.getElementById('mycolgroup');

  // The first time this function is called for a given table, set up an
  // array of reverse sort flags.
  if (divEl.reverseSort == null) 
  { divEl.reverseSort = new Array();
    divEl.lastColumn = 1; // assume it is sorted
  }

  // Set the table display style to "none" - necessary for Netscape 6 browsers.
  var oldDsply = tblEl.style.display;
  tblEl.style.display = "none";
  divEl.myarray = new Array();
/* 
  for(var i=0; i < cgEl.childNodes.length; i++) 
  { if (cgEl.childNodes[i].className.search('namecol') >= 0)
      namecol = i;
  }
*/
  for (var i = 0; i < numrows; i++) 
  { divEl.myarray[i] = new Array();
    divEl.myarray[i][1] = i;
/*	if((namecol >= 0) && (namecol != col))
	{ if(!tblEl.rows[i].cells[namecol])
		divEl.myarray[i][2] = "";
	  else
		divEl.myarray[i][2] = StripAccents(getTextValue(tblEl.rows[i].cells[namecol].innerHTML));
	}
*/
  }

  // If this column has not been sorted before, set the initial sort direction.
  
  /* rev values: 0=start normal; 1=start reverse; 2=always normal; 3= always reverse */
  /* with 0 and 1 the second sort for the same column will inverse the sort */
  
  if(rev > 1)
  { divEl.reverseSort[col] = rev-2;
    if(col != divEl.lastColumn)
	{ divEl.reverseSort[divEl.lastColumn] = 0;
	}
  }
  else if (col != divEl.lastColumn)
  { divEl.reverseSort[col] = rev;
    divEl.reverseSort[divEl.lastColumn] = 0;
  }
  else  // If this column was the last one sorted, reverse its sort direction.
  { divEl.reverseSort[col] = 1 - divEl.reverseSort[col];
  }
  reversesort = divEl.reverseSort[col];
  divEl.lastColumn = col;

  // Sort the rows based on the content of the specified column using a
  // selection sort.
  for (i = 0; i < numrows; i++) 
  { if(!tblEl.rows[i].cells[col]) /* if erased row */
    { divEl.myarray[i][0] = ""; 
	}
    else if(tblEl.rows[i].cells[col].getAttribute('srt'))
    { divEl.myarray[i][0] = tblEl.rows[i].cells[col].getAttribute('srt');
	}
    else if(tblEl.rows[i].cells[col].getElementsByTagName('input').length)
    {  divEl.myarray[i][0] = tblEl.rows[i].cells[col].children[0].value;
	}
    else if(col == namecol)
	{ divEl.myarray[i][0] = StripAccents(getTextValue(tblEl.rows[i].cells[col].innerHTML));
	}
	else
	{ divEl.myarray[i][0] = getTextValue(tblEl.rows[i].cells[col].innerHTML);
	}
  }

  var isnumeric = true;
  for (i = 0; i < tblEl.rows.length; i++) 
  { if(isNaN(divEl.myarray[i][0]))
    { isnumeric = false; break; 
	}
  }
  
  /* look for fields starting with "<". If there are remove all html */
  var htmlpresent = false;
  for (i = 0; i < tblEl.rows.length; i++)
  { if(divEl.myarray[i][0] != "")
	{ if(divEl.myarray[i][0].substring(0,1) == "<")
		htmlpresent = true;
	  break;
	}
  }
  
  if(htmlpresent)
  { for (i = 0; i < tblEl.rows.length; i++)
	{ divEl.myarray[i][0] = divEl.myarray[i][0].replace(/<[\>]*>/g,''); /* remove html tags */
	}
  }
	  

  if(!isnumeric && (cgEl.childNodes[col].className.search('numeric') >= 0)) // the case of 10a, 10b, 11 
  { for (i = 0; i < numrows; i++) 
      divEl.myarray[i][0] = normalizeNum(divEl.myarray[i][0]);
	for (i = 0; i < numrows; i++) 
      divEl.myarray[i][0] = divEl.myarray[i][0].substring(maxgap);
  }

  if(namecol == col) namecol = -1; // optimalization
  if(isnumeric)
    divEl.myarray.sort(mynumsort);
  else
    divEl.myarray.sort(mysort);
  mynodes = new Array();
  for (i = numrows-1; i >=0; i--) 
    mynodes[i] = tblEl.removeChild(tblEl.childNodes[i]);
	
  for (i = 0; i <numrows; i++) 
    tblEl.appendChild(mynodes[divEl.myarray[i][1]]);
	
  tblEl.style.display = oldDsply;

  return false;
}

function upsideDown(id)
{ var tblEl = document.getElementById(id);
  var cgEl = document.getElementById('mycolgroup');
  var divEl = document.getElementById('testdiv');
  divEl.reverseSort = new Array(); // reset if there was a sort flag
  divEl.lastColumn = 1; // assume it is sorted
  var numrows = tblEl.rows.length;
  for(i=0; i<numrows-1; i++)
    tblEl.insertBefore(tblEl.removeChild(tblEl.childNodes[numrows-1]),tblEl.childNodes[i]);
  return false;
}

function getTextValue(s) 
{ s = s.replace(/<[^>]*>/g, '');
  s = s.replace(/&nbsp;/g, ' ');
  return s;
}

var maxgap = 8;
function normalizeNum(txt) 
{ var k = 0;
  while((txt.charAt(k) >='0') && (txt.charAt(k) <='9'))
    k++;
//	alert('vvv '+txt+'=='+txt.charAt(k)+'--'+k);
  var gap = 8-k;
  if(gap < maxgap)
    maxgap = gap;
//  alert('ppp '+k);
  var txt2 = '';
  for(j=0; j<gap; j++)
    txt2 += ' ';
  return txt2+txt;
}