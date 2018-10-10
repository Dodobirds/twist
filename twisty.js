/*jshint esversion: 6 */

var letterRack = ['P','L','A','C','E','H']; //Modular size, based on letters in use
var submitRack = ['_','_','_','_','_','_']; //Fixed size, all empty spaces are filled with '_'
var currentLetter = 0;
var totalWords = [0,0,0,0]; //4 index vector, contains [3words,4words,5words,6words]
var completedWords = [0,0,0,0];
var score = 0;

function init() {
  listen();
  update();

}

function pushLetter(val) {
  function remove(e, arr) {
    for (let i = 0; i < arr.length; i++) {
      if (arr[i] === e) {
        arr.splice(i, 1);
        break;
      }
    }
  }
  if (currentLetter < submitRack.length) {
    submitRack[currentLetter++] = val;
    remove(val, letterRack);
    update();
  }
}

function popLetter() {
  if (currentLetter > 0) {
    let temp = submitRack[--currentLetter];
    letterRack.push(temp);
    submitRack[currentLetter] = '_';
    update();
  }
}

//Update the display
function update() {
  var displayLetter = document.querySelectorAll('.letter');
  var displaySubmit = document.querySelectorAll('.enter');
  var displayCompleted = document.querySelectorAll('.solved');
  var displayTotal = document.querySelectorAll('.total');
  var displayScore = document.querySelector('#score');

  function updateArray(display, arr) {
    for (let i = 0; i < display.length; i++) {
      if (i >= arr.length) {
        display[i].innerText = '_';
      }
      else {
        display[i].innerText = arr[i];
      }
    }
  }
  updateArray(displayLetter, letterRack);
  updateArray(displaySubmit, submitRack);
  updateArray(displayCompleted, completedWords);
  updateArray(displayTotal, totalWords);
  displayScore.innerText = score;
}

function typing(e) {
  if (e.keyCode === 13) {
    console.log('this was an enter');
    submit();
  }
  else if (e.keyCode === 8) {
    console.log('this was a backspace');
    popLetter();
  }
  else {
    typed = String.fromCharCode(e.which);
    if (letterRack.includes(typed)) {
      pushLetter(typed);
    }
  }
}

function shuffle(arr) {
//https://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array
  for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]]; // eslint-disable-line no-param-reassign
    }
}

function listen() {
  document.addEventListener("keydown", typing, false);
  document.querySelector('#submit').addEventListener('click', submit);
  document.querySelector('#round').addEventListener('click', newRound);
  document.querySelector('#twist').addEventListener('click', () => {
    shuffle(letterRack);
    update();
  });
}

function getNewRack() {
  var xhr = new XMLHttpRequest();
  xhr.onload = function() {
    if (this.status === 200) {
      let newRack = JSON.parse(this.response);
      letterRack = Array.from(newRack.word);
      totalWords = newRack.count;
      completedWords = totalWords;

      submitRack = ['_','_','_','_','_','_'];
      currentLetter = 0;
      update(); //placed here because this all happens async - read up about Promises i guess...
    }
  };
  xhr.open("GET", 'php/genRack.php');
  xhr.send();
}

function submit() {
  testPost();
  while (currentLetter > 0) {
    popLetter();
  }
}


function testPost(){
  var xhr = new XMLHttpRequest();
  var test = submitRack.join('');
  xhr.onload = function() {
    if (this.status === 200) {
      //console.log(JSON.parse(this.response));
      completedWords = JSON.parse(this.response);
      update();
    }
  };
  xhr.open("POST", 'php/genRack.php');
  //xhr.setRequestHeader("Content-Type", "application/json");
  xhr.send(test);
}

function newRound(){
  getNewRack();
}

init();
