import { displayNavBar } from './functions.js';


var set1, set2, set3 = false;
//array of featured product id's
const featured = [5,2,1];

//add navbar and fills all 3 featured product slots
displayNavBar();
addProductCards(featured);

//takes an array of product ID's and fills the cards with them
async function addProductCards(featured) {
  for(var i = 0; i <= featured.length - 1; i++){
    let cardHTML = await getCard(featured[i]);
    if(!set1){
      document.querySelector('#featured_item_1').innerHTML = cardHTML;
      console.log("set featured slot 1");
      set1 = true;
    } else if (!set2){
      document.querySelector('#featured_item_2').innerHTML = cardHTML;
      console.log("set featured slot 2");
      set2 = true;
    } else if (!set3){
      document.querySelector('#featured_item_3').innerHTML = cardHTML;
      console.log("set featured slot 3");
      set3 = true;
    } else {
      console.log("all 3 featured slots are set");
      break;
    }
  }
}

//gets a card with the respective product ID from index.php
async function getCard(cardNum){
  try{
    let fetchAddr = "index.php?Id=" + cardNum;
    const val = await fetch(fetchAddr);
    if(val.ok){
      return val.text();
    }
  } catch(error){
    console.log(error);
  }
  
}