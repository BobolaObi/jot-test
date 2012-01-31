<?php
/**
 * Utilities for base conversion
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class BaseIntEncoder {

    //const codeset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    //readable character set excluded (0,O,1,l)
    const codeset = "23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";

    static function encode($n){
        $base = strlen(self::codeset);
        $converted = '';

        while ($n > 0) {
            $converted = substr(self::codeset, bcmod($n,$base), 1) . $converted;
            $n = self::bcFloor(bcdiv($n, $base));
        }

        return $converted ;
    }

    static function decode($code){
        $base = strlen(self::codeset);
        $c = '0';
        for ($i = strlen($code); $i; $i--) {
            $c = bcadd($c,bcmul(strpos(self::codeset, substr($code, (-1 * ( $i - strlen($code) )),1))
                    ,bcpow($base,$i-1)));
        }

        return bcmul($c, 1, 0);
    }

    static private function bcFloor($x)
    {
        return bcmul($x, '1', 0);
    }

    static private function bcCeil($x)
    {
        $floor = bcFloor($x);
        return bcadd($floor, ceil(bcsub($x, $floor)));
    }

    static private function bcRound($x)
    {
        $floor = bcFloor($x);
        return bcadd($floor, round(bcsub($x, $floor)));
    }
}

function bcmod( $x, $y )
{
    // how many numbers to take at once? carefull not to exceed (int)
    $take = 5;    
    $mod = '';

    do
    {
        $a = (int)$mod.substr( $x, 0, $take );
        $x = substr( $x, $take );
        $mod = $a % $y;   
    }
    while ( strlen($x) );

    return (int)$mod;
} 

function bcdiv( $first, $second, $scale = 0 ){
    $res = $first / $second;
    return round( $res, $scale );
}
    
function bcmul($_ro, $_lo, $_scale=0){
    return round($_ro*$_lo, $_scale);
}
  

function bcadd($zahl1,$zahl2) {
    Global $_MPM; $_MPM['Serial'][] = "bcadd($zahl1,$zahl2)";

    if ($zahl1===false or $zahl2===false) {return false;}

    # detect sign
    $vorzeichen1=1;$vorzeichen2=1;
    if (substr($zahl1,0,1)=="-") {$vorzeichen1=-1;$zahl1=substr($zahl1,1);}
    if (substr($zahl1,0,1)=="+") {$vorzeichen1=1;$zahl1=substr($zahl1,1);}
    if (substr($zahl2,0,1)=="-") {$vorzeichen2=-1;$zahl2=substr($zahl2,1);}
    if (substr($zahl2,0,1)=="+") {$vorzeichen2=1;$zahl2=substr($zahl2,1);}

    if ($vorzeichen1==1 and $vorzeichen2==-1) {return bcsub("$zahl1","$zahl2");}
    if ($vorzeichen1==-1 and $vorzeichen2==-1)
      {
      if ($zahl1==0 and $zahl2==0) {return 0;}
      return "-".bcadd ("$zahl1","$zahl2");
      }
    if ($vorzeichen1==-1 and $vorzeichen2==1) {return bcsub("$zahl2","$zahl1");}

    if ($zahl1==0 and $zahl2==0) {return 0;}
    if ($zahl1==0) {return $zahl2;}
    if ($zahl2==0) {return $zahl1;}

    # delete leading zeros
    while (substr($zahl1,0,1) == "0") {$zahl1=substr($zahl1,1,strlen($zahl1)+1);}
    while (substr($zahl2,0,1) == "0") {$zahl2=substr($zahl2,1,strlen($zahl2)+1);}

    # delete positive sign
    while (substr($zahl1,0,1) == "+") {$zahl1=substr($zahl1,1,strlen($zahl1)+1);}
    while (substr($zahl2,0,1) == "+") {$zahl2=substr($zahl2,1,strlen($zahl2)+1);}

    # detect positon of decimal place
    $position1=strpos($zahl1,".");
    $position2=strpos($zahl2,".");


    # delete trailing zeros in $zahl1 (only if there is a decimal point) and delete unnecessary decimal point
    if (!$position1===false)  {
      # delete trailing zeros
      while (substr($zahl1,strlen($zahl1)-1,strlen($zahl1)) == "0") {
        $zahl1=substr($zahl1,0,strlen($zahl1)-1);
        }
      # delete decimal point if it is the last character
      if (substr($zahl1,strlen($zahl1)-1,strlen($zahl1)) == ".") {
        $zahl1=substr($zahl1,0,strlen($zahl1)-1);
        $position1=false; # set position of decimal point false (= there is no point anymore)
        }
      }

    # delete trailing zeros in $zahl2 (only if there is a decimal point) and delete unnecessary decimal point
    if (!$position2===false) {
      # delete trailing zeros
      while (substr($zahl2,strlen($zahl2)-1,strlen($zahl2)) == "0") {
        $zahl2=substr($zahl2,0,strlen($zahl2)-1);
        }
      # delete decimal point if it is the last character
      if (substr($zahl2,strlen($zahl2)-1,strlen($zahl2)) == ".") {
        $zahl2=substr($zahl2,0,strlen($zahl2)-1);
        $position2=false; # set position of decimal point false (= there is no point anymore)
        }
      }

    # number of digits after the point
    $pos1 = ($position1===false) ? false : strlen($zahl1)-($position1+1);
    $pos2 = ($position2===false) ? false : strlen($zahl2)-($position2+1);

    # numeric characters BEFORE point
    $teil_vor_komma1 = ($pos1===false) ? $zahl1 : substr($zahl1,0,strlen($zahl1)-$pos1-1);
    $teil_vor_komma2 = ($pos2===false) ? $zahl2 : substr($zahl2,0,strlen($zahl2)-$pos2-1);

    # numeric characters AFTER point
    $teil_hinter_komma1 = ($pos1===false) ? "" : substr($zahl1,-$pos1);
    $teil_hinter_komma2 = ($pos2===false) ? "" : substr($zahl2,-$pos2);

    # add trailing zeros
    if (substr_count($zahl1,".")==1) {
      $pos=strpos($zahl1,".");
      if (strlen($teil_hinter_komma1)<strlen($teil_hinter_komma2)) {
        $zahl1=$zahl1.str_repeat("0",strlen($teil_hinter_komma2)-strlen($teil_hinter_komma1));
        }
      }
    else {
      # there is no decimal point: add one
      if (strlen($teil_hinter_komma1)<strlen($teil_hinter_komma2)) {
        $zahl1=$zahl1.".".str_repeat("0",strlen($teil_hinter_komma2)-strlen($teil_hinter_komma1));
        }
      }
    if (substr_count($zahl2,".")==1) {
      $pos=strpos($zahl2,".");
      if (strlen($teil_hinter_komma2)<strlen($teil_hinter_komma1)) {
        $zahl2=$zahl2.str_repeat("0",strlen($teil_hinter_komma1)-strlen($teil_hinter_komma2));
        }
      }
    else {
      # there is no decimal point: add one
      if (strlen($teil_hinter_komma2)<strlen($teil_hinter_komma1)) {
        $zahl2=$zahl2.".".str_repeat("0",strlen($teil_hinter_komma1)-strlen($teil_hinter_komma2));
        }
      }

    # add leading zeros
    if (strlen($teil_vor_komma1)<strlen($teil_vor_komma2)) {$zahl1=str_repeat("0",strlen($teil_vor_komma2)-strlen($teil_vor_komma1)).$zahl1;}
    if (strlen($teil_vor_komma1)>strlen($teil_vor_komma2)) {$zahl2=str_repeat("0",strlen($teil_vor_komma1)-strlen($teil_vor_komma2)).$zahl2;}

    # detect positon of decimal place of future result
    $pos = (($pos=strpos($zahl1,"."))===false) ? 0 : strlen($zahl1)-($pos+1);

    # delete point
    $zahl1=str_replace(".","",$zahl1);
    $zahl2=str_replace(".","",$zahl2);

    $ueberlauf =0;
    $fg = '';
    for ($i=strlen($zahl1)-1;$i>=0;$i--){
      $a=substr($zahl1,$i,1);
      $b=substr($zahl2,$i,1)+$ueberlauf;
      $er=$a+$b;
      if ($er>9 and $i!=0) {$ueberlauf=substr($er,0,strlen($er)-1);$er=substr($er,-1);} else {$ueberlauf=0;}
      $fg=$er.$fg;
      }

    if ($fg==0) {return 0;}

    # set decimal point
    if ($pos) {
      #echo "fg: $fg<br />",
      $rechts=substr($fg,-$pos);

      $links=substr($fg,0,strlen($fg)-$pos); #echo "pos: $pos ** l: $links ** r: $rechts<hr>";

      $fg="${links}.${rechts}";
      $position=strpos($zahl1,".");
      # delete trailing zeros
      while (substr($fg,strlen($fg)-1,strlen($fg)) == "0") {$fg=substr($fg,0,strlen($fg)-1);}
      # delete trailing decimal point if it is the last sign
      if (substr($fg,strlen($fg)-1,strlen($fg)) == ".") {$fg=substr($fg,0,strlen($fg)-1);}
      }

    # delete leading zeros
    $vorzeichen = '';
    while (substr($fg,0,1) == "0") {
      $fg=substr($fg,1,strlen($fg)+1);
      if ($fg=="0") {return 0;}
      }
    if ($fg=="0") {return 0;}
    
    return $vorzeichen. round($fg);
} ## end bcadd()

function bcpow($num, $power) {
    $awnser = "1";
    while ($power) {
        $awnser = bcmul($awnser, $num, 100);
        $power = bcsub($power, "1");
    }
    return round(rtrim($awnser, '0.'));
}

function bcsub($zahl1,$zahl2) {
    Global $_MPM; $_MPM['Serial'][] = "bcsub($zahl1,$zahl2)";

    if ($zahl1===false or $zahl2===false) {return false;}

    # detect sign
    $vorzeichen1=1;$vorzeichen2=1;
    if (substr($zahl1,0,1)=="-") {$vorzeichen1=-1;$zahl1=substr($zahl1,1);}
    if (substr($zahl1,0,1)=="+") {$vorzeichen1=1;$zahl1=substr($zahl1,1);}
    if (substr($zahl2,0,1)=="-") {$vorzeichen2=-1;$zahl2=substr($zahl2,1);}
    if (substr($zahl2,0,1)=="+") {$vorzeichen2=1;$zahl2=substr($zahl2,1);}

    if ($vorzeichen1==-1 and $vorzeichen2==1) {return "-".bcadd("$zahl1","$zahl2");}
    if ($vorzeichen1==1 and $vorzeichen2==-1) {return bcadd("$zahl1","$zahl2");}
    if ($vorzeichen1==-1 and $vorzeichen2==-1) {return bcadd("-$zahl1","$zahl2");}

    if ($zahl1==0 and $zahl2==0) {return 0;}
    if ($zahl1==0) {return "-$zahl2";}
    if ($zahl2==0) {return $zahl1;}

    # delete leading zeros
    while (substr($zahl1,0,1) == "0") {$zahl1=substr($zahl1,1,strlen($zahl1)+1);}
    while (substr($zahl2,0,1) == "0") {$zahl2=substr($zahl2,1,strlen($zahl2)+1);}

    # detect positon of decimal place
    $position1=strpos($zahl1,".");
    $position2=strpos($zahl2,".");

    # delete trailing zeros in $zahl1 (only if there is a decimal point) and delete unnecessary decimal point
    if (!$position1===false) {
      # delete trailing zeros
      while (substr($zahl1,strlen($zahl1)-1,strlen($zahl1)) == "0") {
        $zahl1=substr($zahl1,0,strlen($zahl1)-1);
        }
      # delete decimal point if it is the last character
      if (substr($zahl1,strlen($zahl1)-1,strlen($zahl1)) == ".") {
        $zahl1=substr($zahl1,0,strlen($zahl1)-1);
        $position1=false; # set position of decimal point false (= there is no point anymore)
        }
      }

    # delete trailing zeros in $zahl2 (only if there is a decimal point) and delete unnecessary decimal point
    if (!$position2===false) {
      # delete trailing zeros
      while (substr($zahl2,strlen($zahl2)-1,strlen($zahl2)) == "0") {
        $zahl2=substr($zahl2,0,strlen($zahl2)-1);
        }
      # delete decimal point if it is the last character
      if (substr($zahl2,strlen($zahl2)-1,strlen($zahl2)) == ".") {
        $zahl2=substr($zahl2,0,strlen($zahl2)-1);
        $position2=false; # set position of decimal point false (= there is no point anymore)
        }
      }

    # number of digits after the point
    $pos1 = ($position1===false) ? false : strlen($zahl1)-($position1+1);
    $pos2 = ($position2===false) ? false : strlen($zahl2)-($position2+1);

    # numeric characters BEFORE point
    $teil_vor_komma1 = ($pos1===false) ? $zahl1 : substr($zahl1,0,strlen($zahl1)-$pos1-1);
    $teil_vor_komma2 = ($pos2===false) ? $zahl2 : substr($zahl2,0,strlen($zahl2)-$pos2-1);

    # numeric characters AFTER point
    $teil_hinter_komma1 = ($pos1===false) ? "" : substr($zahl1,-$pos1);
    $teil_hinter_komma2 = ($pos2===false) ? "" : substr($zahl2,-$pos2);

    # add trailing zeros
    if (substr_count($zahl1,".")==1) {
      $pos=strpos($zahl1,".");
      if (strlen($teil_hinter_komma1)<strlen($teil_hinter_komma2)) {$zahl1=$zahl1.str_repeat("0",strlen($teil_hinter_komma2)-strlen($teil_hinter_komma1));}
      }
    else {
      # there is no decimal point: add one
      if (strlen($teil_hinter_komma1)<strlen($teil_hinter_komma2)) {$zahl1=$zahl1.".".str_repeat("0",strlen($teil_hinter_komma2)-strlen($teil_hinter_komma1));}
      }
    if (substr_count($zahl2,".")==1) {
      $pos=strpos($zahl2,".");
      if (strlen($teil_hinter_komma2)<strlen($teil_hinter_komma1)) {$zahl2=$zahl2.str_repeat("0",strlen($teil_hinter_komma1)-strlen($teil_hinter_komma2));}
      }
    else {
      # there is no decimal point: add one
      if (strlen($teil_hinter_komma2)<strlen($teil_hinter_komma1)) {$zahl2=$zahl2.".".str_repeat("0",strlen($teil_hinter_komma1)-strlen($teil_hinter_komma2));}
      }

    # add leading zeros
    if (strlen($teil_vor_komma1)<strlen($teil_vor_komma2)) {$zahl1=str_repeat("0",strlen($teil_vor_komma2)-strlen($teil_vor_komma1)).$zahl1;}
    if (strlen($teil_vor_komma1)>strlen($teil_vor_komma2)) {$zahl2=str_repeat("0",strlen($teil_vor_komma1)-strlen($teil_vor_komma2)).$zahl2;}

    # swap zahl1 and zahl2, if zahl2 is bigger than zahl1
    if ($teil_vor_komma1<$teil_vor_komma2) {$help=$zahl2;$zahl2=$zahl1;$zahl1=$help;$vorzeichen="-";}
    if (($teil_vor_komma1==$teil_vor_komma2) and ($teil_hinter_komma1<$teil_hinter_komma2)) {$help=$zahl2;$zahl2=$zahl1;$zahl1=$help;$vorzeichen="-";}

    # detect positon of decimal place of future result
    $pos = (($pos=strpos($zahl1,"."))===false) ? 0 : strlen($zahl1)-($pos+1);

    # delete point
    $zahl1=str_replace(".","",$zahl1);
    $zahl2=str_replace(".","",$zahl2);

    $ueberlauf=0;
    $fg = '';
    for ($i=strlen($zahl1)-1;$i>=0;$i--) {
      $a=substr($zahl1,$i,1);
      $b=substr($zahl2,$i,1)+$ueberlauf;
      if ($b>$a) {$a="1".$a;$ueberlauf=1;} else {$ueberlauf=0;}
      $er=$a-$b;
      $fg=$er.$fg;
      }

    if ($fg==0) {return 0;}

    # set decimal point
    if ($pos) {
      $rechts=substr($fg,-$pos);
      $links=substr($fg,0,strlen($fg)-$pos);
      $fg="${links}.${rechts}";
      $position=strpos($zahl1,".");
      # delete trailing zeros
      while (substr($fg,strlen($fg)-1,strlen($fg)) == "0") {$fg=substr($fg,0,strlen($fg)-1);}
      # delete trailing decimal point if it is the last sign
      if (substr($fg,strlen($fg)-1,strlen($fg)) == ".") {$fg=substr($fg,0,strlen($fg)-1);}
      }

    # delete leading zeros
    $vorzeichen = '';
    while (substr($fg,0,1) == "0") {
      $fg=substr($fg,1,strlen($fg)+1);
      if ($fg == '0') return 0;
      }
     if ($fg == '0') { exit; return 0; }

    return $vorzeichen.$fg;
} ## end bcsub()

