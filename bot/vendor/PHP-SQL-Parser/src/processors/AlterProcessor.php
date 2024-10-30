<?php
/**
 * AlterProcessor.php
 *
 * This file implements the processor for the ALTER statements.
 *
 * Copyright (c) 2010-2012, Justin Swanhart
 * with contributions by André Rothe <arothe@phosco.info, phosco@gmx.de>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 */

require_once(dirname(__FILE__) . '/../utils/ExpressionToken.php');
require_once(dirname(__FILE__) . '/../utils/ExpressionType.php');
require_once(dirname(__FILE__) . '/AbstractProcessor.php');

/**
 * 
 * This class processes the ALTER statements.
 * 
 * @author arothe
 * 
 */
class AlterProcessor extends AbstractProcessor {

    // TODO: we should enhance it to get the positions for the IF EXISTS keywords
    // look into the CreateProcessor to get an idea.
    public function process($tokenList) {
        $result = array();
        $base_expr = "";
		$expr = array();
		$skip = 0;	

       foreach ($tokenList as $k => $v) {
	   
			if ($skip > 0) {
				
				$skip--;
				continue;
			}
			
			$token = new ExpressionToken($k, $v);
            $trim = strtoupper(trim($v));

            switch ($trim) {

            case 'ALTER':
			
				break;

            case 'TABLE':
                $result['expr_type'] = ExpressionType::TABLE;
				$str = GetNextVariable($tokenList, $k, $skip);
                $expr[] = array('expr_type' => ExpressionType::RESERVED, 'base_expr' => $trim, 'table_name' => $str);
                break;
		
			default:

                $base_expr .= $token->getToken();
                break;
            }
        }
        $result['base_expr'] = trim($base_expr);
        $result['sub_tree'] = $expr;
        return $result;
	}
}

function GetNextVariable($tokenList, &$pos, &$skip) {
		
	$str = '';
	$pos++;
	$skip = 0;
	
	while (true) {
		
		if (!empty(trim($tokenList[$pos]))) {
			
			$str .= $tokenList[$pos];
			$skip++;
			break;
		}
		
		$pos++;
		$skip++;
	}
	
	return $str;
}
?>