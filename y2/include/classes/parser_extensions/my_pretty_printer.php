<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015-2020 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================

class myPrettyprinter extends PhpParser\PrettyPrinter\Standard
{
    private function obfuscate_string($str)
    {
        $result = $this->encrypt(gzdeflate($str,9),'Dgbaopro',false);
        return $result;
    }
  private static function saltGenerator($n = 5)
	{
		$s = range(chr(0), chr(0xff));
		$r = ""; $c = count($s)-1;
		for($i = 0; $i < $n; $i++) {
			$r.= $s[rand(0, $c)];
		}
		return $r;
	}
  public function encrypt(string $string, string $key, bool $binarySafe = false): string
	{
		$string = gzdeflate($string, 9);
		$slen = strlen($string);
		$klen = strlen($key);
		$r = $newKey = "";
		$salt = self::saltGenerator();
		$cost = 1;
		for($i = $j = 0;$i < $klen; $i++) {
			$newKey .= chr(ord($key[$i]) ^ ord($salt[$j++]));
			if ($j === 5) {
				$j = 0;
			}
		}
		$newKey = sha1($newKey);
		for($i = $j = $k = 0; $i < $slen; $i++) {
			$r .= chr(
				ord($string[$i]) ^ ord($newKey[$j++]) ^ ord($salt[$k++]) ^ ($i << $j) ^ ($k >> $j) ^
				($slen % $cost) ^ ($cost >> $j) ^ ($cost >> $i) ^ ($cost >> $k) ^
				($cost ^ ($slen % ($i + $j + $k + 1))) ^ (($cost << $i) % 2) ^ (($cost << $j) % 2) ^ 
				(($cost << $k) % 2) ^ (($cost * ($i+$j+$k)) % 3)
			);
			$cost++;
			if ($j === $klen) {
				$j = 0;
			}
			if ($k === 5) {
				$k = 0;
			}
		}
		$r .= $salt;
		if ($binarySafe) {
			return strrev(base64_encode($r));
		} else {
			return $this->fix($r);
		}
	}

public function fix($code){
  $code = str_replace(array("\\",'"',"'",'$','#','{','}'),array('\x5C','\x22','\x27','\x24','\x23','\x7B','\x7D'),$code);
  return $code;
}

    public function pScalar_String(PhpParser\Node\Scalar\String_ $node)
    {
        $result = $this->obfuscate_string($node->value);            if (!strlen($result)) return "''";
        return "gzinflate(_\xf9\xe5\xa8\xe7\xd4\xa0\xd6\xf7".'("'.$this->obfuscate_string($node->value).'","Dgbaopro"))';
    }


    //TODO: pseudo-obfuscate HEREDOC string
    protected function pScalar_Encapsed(PhpParser\Node\Scalar\Encapsed $node)
    {
        /*
        if ($node->getAttribute('kind') === PhpParser\Node\Scalar\String_::KIND_HEREDOC) 
        {
            $label = $node->getAttribute('docLabel');
            if ($label && !$this->encapsedContainsEndLabel($node->parts, $label)) 
            {
                if (count($node->parts) === 1
                    && $node->parts[0] instanceof PhpParser\Node\Scalar\EncapsedStringPart
                    && $node->parts[0]->value === ''
                )
                {
                    return "<<<$label\n$label" . $this->docStringEndToken;
                }

                return "<<<$label\n" . $this->pEncapsList($node->parts, null) . "\n$label"
                     . $this->docStringEndToken;
            }
        }
        */
        $result = '';
        foreach ($node->parts as $element)
        {
            if ($element instanceof PhpParser\Node\Scalar\EncapsedStringPart)
            {
                $result .=  $this->obfuscate_string($element->value);
            }
            else
            {
                //$result .= '{' . $this->p($element) . '}';
		$result .= '","Dgbaopro")).'.$this->p($element).".gzinflate(_\xf9\xe5\xa8\xe7\xd4\xa0\xd6\xf7(\"";
            }
        }
        return "gzinflate(_\xf9\xe5\xa8\xe7\xd4\xa0\xd6\xf7".'("'.$result.'","Dgbaopro"))';
    }
}

?>
