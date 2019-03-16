<?php

// // ログを取るか
// ini_set('log_errors','on');
// // ログの出力先ファイルを指定
// ini_set('error_log','php.log');
// セッションを使う
session_start();

// モンスターたち格納用
$monsters = array();
// 性別クラス
class Sex {
  const MAN = 1;
  const WOMAN = 2;
}

// 抽象クラス
abstract class Creature {
  protected $name;
  protected $hp;
  protected $attackMin;
  protected $attackMax;
  abstract public function sayCry();
  public function setName($str) {
    $this->name = $str;
  }
  public function getName() {
    return $this->name;
  }
  public function setHp($num) {
    $this->hp = $num;
  }
  public function getHp() {
    return $this->hp;
  }
  public function attack($targetObj) {
    $attackPoint = mt_rand($this->attackMin,$this->attackMax);
    if(!mt_rand(0,9)) {
      $attackPoint = $attackPoint * 1.5;
      $attackPoint = (int)$attackPoint;
      History::set($this->getName().'のクリティカルヒット！');
    }
    $targetObj->setHp($targetObj->getHp() - $attackPoint);
    History::set($attackPoint.'ポイントのダメージ！');
  }
}


// 人クラス
class Human extends Creature {
  protected $sex;
  public function __construct($name,$sex,$hp,$attackMin,$attackMax) {
    $this->name = $name;
    $this->sex = $sex;
    $this->hp = $hp;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
  public function setSex($num) {
    $this->sex = $num;
  }
  public function getSex() {
    return $this->sex;
  }
  public function sayCry() {
    History::set($this->name.'が叫ぶ！');
    switch($this->sex) {
      case Sex::MAN :
        History::set('ぐはぁ！');
        break;
      case Sex::WOMAN :
        History::set('きゃっ！');
        break;
    }
  }
}

// モンスタークラス
class Monster extends Creature {
  // プロパティ
  protected $img;
  // コンストラクタ
  public function __construct($name,$hp,$img,$attackMin,$attackMax) {
    $this->name = $name;
    $this->hp = $hp;
    $this->img = $img;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }

  // ゲッター
  public function getImg() {
    return $this->img;
  }
  public function sayCry() {
    History::set($this->name.'が叫ぶ！');
    History::set('ガオッ！');
  }
}


// 魔法を使えるモンスタークラス
class MagicMonster extends Monster {
  private $magicAttack;
  function __construct($name,$hp,$img,$attackMin,$attackMax,$magicAttack) {
    parent::__construct($name,$hp,$img,$attackMin,$attackMax);
    $this->magicAttack = $magicAttack;
  }
  public function getMagicAttack() {
    return $this->magicAttack;
  }
  public function attack($targetObj) {
    if(!mt_rand(0,4)) {
      History::set($this->name.'の魔法攻撃！');
      $targetObj->setHp( $targetObj->getHp() - $this->magicAttack);
      History::set($this->magicAttack.'ポイントのダメージを受けた！');
    }else{
      parent::attack($targetObj);
    }
  }
}


// インターフェース
interface HistoryInterface {
  public static function set($str);
  public static function clear();
}


// 履歴管理クラス
class History implements HistoryInterface {
  public static function set($str) {
    // セッションhistoryが作られなければ作る
    if(empty($_SESSION['history'])) $_SESSION['history'] = '';
    // 文字列をセッションhistoryへ格納
    $_SESSION['history'] .= $str.'<br />';
  }
  public static function clear() {
    unset($_SESSION['history']);
  }
}


// インスタンスの生成
$human = new Human('勇者見習い',Sex::MAN,500,40,120);
$monsters[] = new Monster('卵',80,'img/monsters/monster01.png',10,20);
$monsters[] = new Monster( '恐竜の赤ちゃん', 120, 'img/monsters/monster02.png', 20, 40);
$monsters[] = new Monster( '緑の恐竜', 200, 'img/monsters/monster03.png', 30, 50);
$monsters[] = new Monster( 'ティロサウルス', 250, 'img/monsters/monster04.png', 40, 60);
$monsters[] = new Monster( 'ステロサウルス', 250, 'img/monsters/monster05.png', 40, 60 );
$monsters[] = new Monster( 'トロサウルス', 280, 'img/monsters/monster06.png', 40, 65 );
$monsters[] = new MagicMonster( 'ティラノサウルス', 350, 'img/monsters/monster07.png', 40, 60, mt_rand(100,200) );
$monsters[] = new MagicMonster( 'メガティラノサウルス', 380, 'img/monsters/monster08.png', 50, 70, mt_rand(120,220) );


// モンスターの生成
function createMonster() {
  global $monsters;
  $monster = $monsters[mt_rand(0,7)];
  History::set($monster->getName().'が現れた！');
  $_SESSION['monster'] = $monster;
}


// 人の生成
function createHuman() {
  global $human;
  $_SESSION['human'] = $human;
}


// 初期化
function init() {
  History::clear();
  History::set('初期化します！');
  $_SESSION['knockDownCount'] = 0;
  createHuman();
  createMonster();
}


// ゲームオーバー
function gameOver() {
  $_SESSION = array();
}


// POST送信されていた場合
if(!empty($_POST)) {
  $attackFlg = (!empty($_POST['attack'])) ? true : false;
  $startFlg = (!empty($_POST['start'])) ? true : false;
  error_log('POSTされた！');


  if($startFlg) {
    History::set('ゲームスタート！');
    init();
  }else {
    // 攻撃するを押した場合
    if($attackFlg) {

      // モンスターに攻撃を与える
      History::set($_SESSION['human']->getName().'の攻撃！');
      $_SESSION['human']->attack($_SESSION['monster']);
      $_SESSION['monster']->sayCry();

      // モンスターが攻撃をする
      History::set($_SESSION['monster']->getName().'の攻撃！');
      $_SESSION['monster']->attack($_SESSION['human']);
      $_SESSION['human']->sayCry();


      // 自分のHPが０以下になったらゲームオーバー
      if($_SESSION['human']->getHp() <= 0) {
        gameOver();
      }else {
        // HPが０以下になったら、別のモンスターを出現させる
        if($_SESSION['monster']->getHp() <= 0) {
          History::set($_SESSION['monster']->getName().'を倒した！');
          createMonster();
          $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
        }
      }
    }else { //逃げるを押した場合
      History::set('逃げた！');
      createMonster();
    }
  }
  $_POST = array();
}


?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>モンスタークエスト！！！</title>
    <style>
    	body{
	    	margin: 0 auto;
	    	padding: 150px;
	    	width: 25%;
	    	background: #fbfbfa;
        color: white;
    	}
    	h1{ color: white; font-size: 20px; text-align: center;}
      h2{ color: white; font-size: 16px; text-align: center;}
    	form{
	    	overflow: hidden;
    	}
    	input[type="text"]{
    		color: #545454;
	    	height: 60px;
	    	width: 100%;
	    	padding: 5px 10px;
	    	font-size: 16px;
	    	display: block;
	    	margin-bottom: 10px;
	    	box-sizing: border-box;
    	}
      input[type="password"]{
    		color: #545454;
	    	height: 60px;
	    	width: 100%;
	    	padding: 5px 10px;
	    	font-size: 16px;
	    	display: block;
	    	margin-bottom: 10px;
	    	box-sizing: border-box;
    	}
    	input[type="submit"]{
	    	border: none;
	    	padding: 15px 30px;
	    	margin-bottom: 15px;
	    	background: black;
	    	color: white;
	    	float: right;
    	}
    	input[type="submit"]:hover{
	    	background: #3d3938;
	    	cursor: pointer;
    	}
    	a{
	    	color: #545454;
	    	display: block;
    	}
    	a:hover{
	    	text-decoration: none;
    	}
    </style>
  </head>
  <body>
   <h1 style="text-align:center; color:#333;">モンスタークエスト！！！</h1>
    <div style="background:black; padding:15px; position:relative;">
      <?php if(empty($_SESSION)){ ?>
        <h2 style="margin-top:60px;">GAME START ?</h2>
        <form method="post">
          <input type="submit" name="start" value="▶ゲームスタート">
        </form>
      <?php }else{ ?>
        <h2><?php echo $_SESSION['monster']->getName().'が現れた!!'; ?></h2>
        <div style="height: 150px;">
          <img src="<?php echo $_SESSION['monster']->getImg(); ?>" style="width:120px; height:auto; margin:40px auto 0 auto; display:block;">
        </div>
        <p style="font-size:14px; text-align:center;">モンスターのHP：<?php echo $_SESSION['monster']->getHp(); ?></p>
        <p>倒したモンスター数：<?php echo $_SESSION['knockDownCount']; ?></p>
        <p>勇者の残りHP：<?php echo $_SESSION['human']->getHp(); ?></p>
        <form method="post">
          <input type="submit" name="attack" value="▶攻撃する">
          <input type="submit" name="escape" value="▶逃げる">
          <input type="submit" name="start" value="▶ゲームリスタート">
        </form>
      <?php } ?>
      <div style="position:absolute; right:-350px; top:0; color:black; width: 300px;">
        <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
      </div>
    </div>

  </body>
</html>
