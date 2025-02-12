// see here for a list:  http://en.wikipedia.org/wiki/Alphabets_derived_from_the_Latin
// se Notepad++ to see all chars.
function StripAccents(theword)
{ var letter, tempWord, charcase;
  tempWord = '';
  for(var i = 0; i <theword.length; i++) /* optimalisation */
  { if(theword.charCodeAt(i) >= 128) 
      break;
  }
  if(i == theword.length)
     return theword;
  for(i = 0; i <theword.length; i++)
  { letter = theword.charAt(i);
    if(letter <= "~") 
    { tempWord = tempWord.concat(letter);
      continue;
    }
    if(letter == letter.toLowerCase())
      charcase = false;
    else
      charcase = true;
    switch (letter.toLowerCase())
    { case 'á': case 'à': case 'â': case 'ä': case 'å': case 'ą': 
      case 'ă': case 'ã': case 'ǻ': case 'ā':
        letter = 'a';
        break;
      case 'æ':
        letter = 'ae'; case 'ǽ':
        break;
       case 'ß':
        letter = 'ss';
        break;
     case 'ç': case 'č': case 'ć': case 'ĉ': case 'ċ':
        letter = 'c';
        break;
      case 'đ': case 'ď': case 'ð': 
        letter = 'd';
       	break;
      case 'é': case 'è': case 'ė': case 'ê': case 'ë': case 'ě': 
      case 'ĕ': case 'ē': case 'ę': 
        letter = 'e';
        break;
      case 'ğ': case 'ģ': case 'ġ':
        letter = 'g';
        break;
      case 'ĥ': case 'ħ':
        letter = 'h';
        break;
      case 'ı': case 'í': case 'ì': case 'î': case 'ï': case 'ĭ':
      case 'ī': case 'ĩ': case 'į':
        letter = 'i';
        break;
      case 'ĳ':
        letter = 'ij';
        break;
      case 'ĵ':
        letter = 'j';
        break;
      case 'ķ':
        letter = 'k';
        break;
      case 'ĺ': case 'ļ': case 'ł': case 'ľ': case 'ŀ':
        letter = 'l';
        break;
      case 'ŉ': case 'ń': case 'n̈': case 'ň': case 'ñ': case 'ń': 
      case 'ņ': case 'ŋ':
        letter = 'n';
       	break;
      case 'ó': case 'ò': case 'ô': case 'ö': case 'ŏ': case 'ō':
      case 'õ': case 'ő': case 'ø': case 'ǿ':
        letter = 'o';
        break;
      case 'œ':
        letter = 'oe';
        break;
      case 'ř': case 'ŕ': case 'ŗ':
        letter = 'r';
        break;
      case 'ś': case 'ŝ': case 'š': case 'ş': 
        letter = 's';
        break;
      case 'ţ': case 'ť': case 'ŧ': case 'þ':
        letter = 't';
        break;
      case 'ú': case 'ù': case 'û': case 'ü': case 'ů': case 'ŭ':
      case 'ū': case 'ũ': case 'ű': case 'ů': case 'ų':
        letter = 'u';
        break;
     case 'ẃ': case 'ẁ': case 'ŵ': case 'ẅ':
        letter = 'w';
        break;
      case 'ý': case 'ỳ': case 'ŷ': case 'ÿ':
        letter = 'y';
        break;
      case 'ź': case 'ż': case 'ž': 
        letter = 'z';
        break;
       case '': 
        letter = "'";
        break;
   }
    if(charcase) 
      letter = letter.toUpperCase();
    tempWord = tempWord.concat(letter);
  }
  return tempWord;
}

/* function borrowed from http://stackoverflow.com/questions/6274339/how-can-i-shuffle-an-array-in-javascript
*/
function shuffle(array) {
    var counter = array.length, temp, index;

    // While there are elements in the array
    while (counter > 0) {
        // Pick a random index
        index = Math.floor(Math.random() * counter);

        // Decrease counter by 1
        counter--;

        // And swap the last element with it
        temp = array[counter];
        array[counter] = array[index];
        array[index] = temp;
    }

    return array;
}

function menuclick(elt)
{ alert("His "+elt.tagName)
}

function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}

/* it looks like this function is quite slow. */
function removeElementsByClass(className){
    var elements = document.getElementsByClassName(className);
    while(elements.length > 0){
        elements[0].parentNode.removeChild(elements[0]);
    }
}

function isNumber(n) {
	return !isNaN(parseFloat(n)) && isFinite(n);
}

// Source https://davidwalsh.name/javascript-debounce-function
// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
};