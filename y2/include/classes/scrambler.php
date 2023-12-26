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

// case-sensitive:      variable names, constant name, array keys, class properties, labels
// case-insensitive:    function names, class names, class method names, namespaces, keywords and constructs
// classes, interfaces, and traits share the same internal naming_space! only a single Scrambler instance for all of them!


class Scrambler
{
    const SCRAMBLER_CONTEXT_VERSION = '1.1';

    private $t_first_chars          = null;     // allowed first char of a generated identifier
    private $t_chars                = null;     // allowed all except first char of a generated identifier
    private $l1                     = null;     // length of $t_first_chars string
    private $l2                     = null;     // length of $t_chars       string
    private $r                      = null;     // seed and salt for random char generation, modified at each iteration.
    private $scramble_type          = null;     // type on which scrambling is done (i.e. variable, function, etc.)
    private $case_sensitive         = null;     // self explanatory
    private $scramble_mode          = null;     // allowed modes are 'identifier', 'hexa', 'numeric'
    private $scramble_length        = null;     // current length of scrambled names
    private $scramble_length_min    = null;     // min     length of scrambled names
    private $scramble_length_max    = null;     // max     length of scrambled names
    private $t_ignore               = null;     // array where keys are names to ignore.
    private $t_ignore_prefix        = null;     // array where keys are prefix of names to ignore.
    private $t_scramble             = null;     // array of scrambled items (key = source name , value = scrambled name)
    private $t_rscramble            = null;     // array of reversed scrambled items (key = scrambled name, value = source name)
    private $context_directory      = null;     // where to save/restore context
    private $silent                 = null;     // display or not Information level messages.
    private $label_counter          =    0;     // internal label counter.

    private $t_reserved_variable_names = array( 'this','GLOBALS','_SERVER', '_GET', '_POST', '_FILES', '_COOKIE','_SESSION', '_ENV', '_REQUEST',
                                                'php_errormsg','HTTP_RAW_POST_DATA','http_response_header','argc','argv'
                                              );
    private $t_reserved_function_names = array( '__halt_compiler','__autoload', 'abstract', 'and', 'array', 'as', 'bool', 'break', 'callable', 'case', 'catch',
                                                'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else',
                                                'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile',
                                                'eval', 'exit', 'extends', 'false', 'final', 'finally', 'float', 'for', 'foreach', 'function', 'global', 'goto', 'if','fn',
                                                'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'int', 'interface', 'isset', 'list',
                                                'namespace', 'new', 'null', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once',
                                                'return', 'static', 'string', 'switch', 'throw', 'trait', 'true', 'try', 'unset', 'use', 'var', 'while', 'xor','yield',
                                                'apache_request_headers'                        // seems that it is not included in get_defined_functions ..
                                              );

    private $t_reserved_class_names     = array('parent', 'self', 'static',                    // same reserved names for classes, interfaces  and traits...
                                                'int', 'float', 'bool', 'string', 'true', 'false', 'null', 'void', 'iterable', 'object',  'resource', 'scalar', 'mixed', 'numeric','fn'
                                               );

    private $t_reserved_method_names    = array('__construct', '__destruct', '__call', '__callstatic', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__tostring', '__invoke', '__set_state', '__clone','__debuginfo' );


    function __construct($type,$conf,$target_directory)
    {
        global $t_pre_defined_classes,$t_pre_defined_class_methods,$t_pre_defined_class_properties,$t_pre_defined_class_constants;
        global $t_pre_defined_class_methods_by_class,$t_pre_defined_class_properties_by_class,$t_pre_defined_class_constants_by_class;

        $this->scramble_type        = $type;
        $this->t_first_chars        = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->t_chars              = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $this->r                    = md5(microtime(true));     // random seed
        $this->t_scramble           = array();
        $this->silent               = $conf->silent;
        if (isset($conf->scramble_mode))
        {
            switch($conf->scramble_mode)
            {
                case 'numeric':
                    $this->scramble_length_max  = 16;
                    $this->scramble_mode        = $conf->scramble_mode;
                    $this->t_first_chars        = '_';
                    eval('$g = "\x92\xc6\x9e\x99\xfc\xc8\xba\xe3\xe7\xf8\xa0\xd3\xcd\x82\xb0\x9b\xb3\x9e\xf5\xd6\xff\x96\xef\xe9\xf7\xfa\x96\xbb\xc0\x81\x8e\xb6\xeb\xb1\xb8\xd2\xdf\x8c\x90\xf1\xad\xff\xde\xce\xf0\xfd\xe0\xec\xcc\xfc\xf4\xd1\x9c\xee\x9d\xb7\xbe\xbe\xd1\xca\x91\xca\xc0\xfe\xc4\xf4\x86\xc5\xe7\xb0\xc5\xc4\x8a\xd2\xc9\x82\xf6\x97\x8f\x8e\xa7\x8b\xd0\xa4\xc2\xf4\xe0\xab\xca\x86\xd2\x84\x99\xbf\x96\xb6\xec\xe6\xf4\x94\xca\xb7\x82\xc0\xc9\xb0\xcd\xe3\x8d\xad\xb7\x96\x9e\x87\xa4\xe2\xab\xac\xb0\x9e\xf5\xdd\xf1\xe6\xed\xfe\xce\xa7\xb9\xb3\x81\xa7\xca\xa5\xfb\x83\xf9\x89\xc8\xb9\x96\x92\x8e\xbe\xba\x84\xb4\x8e\xed\x9b\xd5\xd7\xf5\xb9\xcb\xca\xa8\x8a\xef\xd5\xd3\xba\x93\x93\xb5\x83\xf4\xab\xe3\x8a\xbe\xde\x9e\xa1\xeb\xbb\xf6\x86\x9e\x9d\xa8\x85\x8f\xb7\xf9\xdb\xf6\xff\xe2\x83\x85\x9b\x86\xb9\xc1\xa2\xbe\xaa\x8b\xe9\xd2\xe4\xd3\xef\xda\x87\xce\xdd\xf8\xfb\xd9\xbc\xbc\xc2\x9d\x9d\xf5\xed\xa4\xf8\x87\xcc\xe6\xe9\xd0\xa7\xe1\x84\xd6\xb9\x9c\xd2\xb5\xcd\xff\xcf\x80\x80\xa0\x81\xae\xbd\xc4\xc1\xe4\xa4\x95\xb4\xd1\x97\xb9\xd3\x88\xd9\xf6\xbd\xb3\x95\xf6\xa5\xef\xc7\xd0\xc5\x9c\xf8\xc6\xcf\xcc\x8e\xf9\x8c\x8f\xfc\x98\xd8\xe1\xd0\x83\x80\xe9\xec\xf8\xdf\xc7\xbc\xb2\xc3\xd3\x98\xfe\xf4\xe7\xfe\xea\xdc\x94\x8b\xe0\xaa\xbf\xd7\xad\xda\xd2\xe0\xcd\xd8\xda\xcd\xbe\x9f\xe6\x98\xe5\xb7\xc0\x91\xd3\xbc\xe7\xb4\xcf\xa9\xbf\x95\xbd\xbe\xc5\xac\xd2\xb6\x91\x85\x95\xc6\xc9\x8b\xc8\xcc\x89\x81\xa1\x92\x80\x80\xfc\xa6\x83\xe3\x93\x99\xc3\xa4\xe7\xf7\xa3\xdd\x99\xa0\xe5\x98\x96\xb6\xba\x82\xec\x9d\xe6\x84\xc5\x94\xda\xcc\x9d\xfc\xc7\xe9\xa7\xe8\xa0\xbc\x95\x9e\xa5\xf9\xf8\x8d\xe1\x96\x94\x8d\xdb\xd5\x94\xf0\xe6\xea\xd1\xf4\xad\xbb\xfb\xb2\x91\xcb\xb8\xf5\xf1\xf6\xbd\x98\xb3\xaa\xa1\x92\xd7\xc1\xd4\xe3\x8e\x9d\xaa\xca\xc2\xab\x8d\x8e\xac\xb4\xdd\xaa\xe3\xc0\xda\xe3\xa8\xcc\xe3\x92\x8b\xf5\xcb\xc8\xd9\xbf\x82\xd0\xac\xdf\xb4\xe8\xfd\xe4\x85\xa6\xf9\xde\xc9\x89\xc1\xad\xcc\xb9\xfb\x84\xa1\xdf\x9c\xa8\xd2\xe0\xf2\x87\xe4\xd0\xdf\x8a\xc7\xeb\xe9\x87\x99\xd9\xe2\x8d\xde\xa2\xca\x8d\x97\x8f\xc4\xbe\xd8\xc5\xa8\x93\xea\x8a\xc3\x9a\xe9\xbc\xf0\xef\x9e\xd5\xef\xe2\xc9\xd9\x86\xc6\xcc\xa3\x98\x8a\xde\xfb\x94\xd6\xde\xcb\xef\x8d\xa4\xd9\x80\x9d\x88\xcc\xd9\x95\xc2\xac\x8f\xfc\xac\xe4\xba\xae\xd5\xe9\x9a\xa5\xfd\xb1\xd2\xf1\xf6\x9e\xf1\x92\x8f\xa9\xb2\xd8\xa3\xa9\x88\x88\xc9\xbc\xa4\xb1\xa1\xf5\x9b\xf3\xbe\xb9\xb1\xe2\xf0\x83\xb7\xad\xc6\xc0\x91\xf7\xba\xc4\x91\xc6\xfb\xae\xe8\xaa\xd3\x90\xe6\xf8\x8d\x90\xb9\x9a\x87\xd0\x9b\xb0\xfe\xb9\x95\xe7\xa7\xa9\x8b\xc3\xdb\x9a\xd8\xfd\x9a\xdc\xce\xb1\xa7\xb4\xb7\xb2\x86\xe6\xcc\xee\xbb\xc7\xf0\xcf\xe4\x9e\xab\x93\xe1\x98\x82\xe9\xe1\xa9\xfc\x82\xcb\xa6\xa2\x92\xa2\xe6\xba\xcd\x94\xc3\x91\xd0\xef\xca\x95\xab\x95\x89\xa1\xed\xc3\xff\x86\x97\xe6\xb1\x82\xd5\x93\xe1\xdb\xb5\x9b\xa1\xa9\xba\xcc\xeb\xe9\xfe\xdd\x8b\xea\xdc\xd6\xcf\xba\x81\x87\x96\xd0\xb3\xf0\xd9\xf0\xf2\xda\x93\xfb\xca\xbb\x96\x9c\xdd\xee\xf7\xf4\xd1\xaa\xb5\xa5\xed\xbb\xe3\xb4\xba\xad\xee\xf6\xbe\xf0\xc1\xbf\xac\xc6\xdf\x8e\x98\xec\xbf\x97\xd5\x80\xa1\x8e\xc3\xaa\xd2\x90\x9a\xdf\xd3\x8a\xdf\xa6\x9a\xce\x97\xd9\xea\xcc\xb9\x89\xa8\x9c\xa2\xa9\xa0\xe0\xb2\xfc\x8b\x84\x91\xc6\x81\xd4\x8e\xa0\xf2\xdc\x98\xf3\xd6\xcd\xbc\xb3\xff\xfc\xf6\x80\xf2\xe8\xf8\xad\xca\x9d\x8b\xf2\xec\xf0\xfc\xea\xaf\xac\x9e\x8e\xd5\xfd\x95\x86\x83\x89\xa0\xfb\xf1\xaf\xa0\xb8\x92\xf5\xdf\xc9\xeb\xac\xd1\xb3\xac\xeb\xc1\xf7\xbb\x85\xcf\x92\xfa\x9a\x8c\xff\xe7\xbc\xe8\xbd\xb3\x80\xdb\xe8\xaf\xd1\xe5\xed\xc5\xa4\xdd\x94\xb5\xa5\xee\xe6\xab\x94\xc4\xa5\xdc\x89\x92\xd7\x95\xf2\xad\xac\xcd\xc9\xbb\xf3\xe4\xde\xcf\x95\xbe\x97\xa9\xee\xe9\xa6\xce\xe3\xc5\xee\xed\xa7\xf9\xe2\xfa\x97\xc4\x87\xc6\xba\x89\xdf\x95\xf1\xeb\xaa\xac\xbf\x88\xed\xe8\x98\x98\x9e\x89\xde\xf7\xe5\x82\xa5\xc6\x87\x89\xa4\xe7\xa3\xa7\xc8\xf4\xd6\x93\xe8\xc0\xb7\x84\xba\xad\xa1\xbd\xc8\xe8\x89\xaf\xb0\xc4\xf7\xa5\x8a\xe4\xa0\xa1\x97\xc9\xad\x97\xfe\xa5\xd3\xc2\x86\xc4\xcd\xb8\xd7\xd5\x94\xf9\xec\xa2\xcc\xee\xc7\xb2\xa6\xcb\xe4\x97\xdc\x82\x8a\xf2\xaf\xa7\xd3\xbb\xd5\xd6\xd3\xbe\xaf\xe0\xc9\x92\xef\x8a\xd4\x8d\xa1\xcf\xdb\xab\x8c\xed\x8b\xfa\xc7\xae\x9a\xec";');
                    $this->t_chars = $g;
                    break;
                case 'hexa':
                    $this->scramble_length_max  = 32;
                    $this->scramble_mode = $conf->scramble_mode;
                    $this->t_first_chars = 'abcdefABCDEF';
                    break;
                case 'identifier':
                default:
                    $this->scramble_length_max  = 16;
                    $this->scramble_mode = 'identifier';
            }
        }
        $this->l1                   = strlen($this->t_first_chars)-1;
        $this->l2                   = strlen($this->t_chars      )-1;
        $this->scramble_length_min  = 2;
        $this->scramble_length      = 5;
        if (isset($conf->scramble_length))
        {
            $conf->scramble_length += 0;
            if ( ($conf->scramble_length >= $this->scramble_length_min) && ($conf->scramble_length <= $this->scramble_length_max) )
            {
                $this->scramble_length  = $conf->scramble_length;
            }
        }
        switch($type)
        {
            case 'constant':
                $this->case_sensitive       = true;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                $this->t_ignore             = array_merge($this->t_ignore,get_defined_constants(false));
                if (isset($conf->t_ignore_constants))
                {
                    $t                      = $conf->t_ignore_constants;                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_constants_prefix))
                {
                    $t                      = $conf->t_ignore_constants_prefix;         $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
            case 'class_constant':
                $this->case_sensitive       = true;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                $this->t_ignore             = array_merge($this->t_ignore,get_defined_constants(false));
                if ($conf->t_ignore_pre_defined_classes!='none')
                {
                    if ($conf->t_ignore_pre_defined_classes=='all') $this->t_ignore = array_merge($this->t_ignore,$t_pre_defined_class_constants);
                    if (is_array($conf->t_ignore_pre_defined_classes))
                    {
                        $t_class_names = array_map('strtolower',$conf->t_ignore_pre_defined_classes);
                        foreach($t_class_names as $class_name)  if (isset($t_pre_defined_class_constants_by_class[$class_name])) $this->t_ignore = array_merge($this->t_ignore,$t_pre_defined_class_constants_by_class[$class_name]);
                    }
                }
                if (isset($conf->t_ignore_class_constants))
                {
                    $t                      = $conf->t_ignore_class_constants;          $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_class_constants_prefix))
                {
                    $t                      = $conf->t_ignore_class_constants_prefix;   $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
            case 'variable':
                $this->case_sensitive       = true;
                $this->t_ignore             = array_flip($this->t_reserved_variable_names);
                if (isset($conf->t_ignore_variables))
                {
                    $t                      = $conf->t_ignore_variables;                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_variables_prefix))
                {
                    $t                      = $conf->t_ignore_variables_prefix;         $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
                /*
             case 'function':
                $this->case_sensitive       = false;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                $t                          = get_defined_functions();                  $t = array_map('strtolower',$t['internal']);    $t = array_flip($t);
                $this->t_ignore             = array_merge($this->t_ignore,$t);
                if (isset($conf->t_ignore_functions))
                {
                    $t                      = $conf->t_ignore_functions;                $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_functions_prefix))
                {
                    $t                      = $conf->t_ignore_functions_prefix;         $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
                */
           case 'property':
                $this->case_sensitive       = true;
                $this->t_ignore             = array_flip($this->t_reserved_variable_names);
                if ($conf->t_ignore_pre_defined_classes!='none')
                {
                    if ($conf->t_ignore_pre_defined_classes=='all') $this->t_ignore = array_merge($this->t_ignore,$t_pre_defined_class_properties);
                    if (is_array($conf->t_ignore_pre_defined_classes))
                    {
                        $t_class_names = array_map('strtolower',$conf->t_ignore_pre_defined_classes);
                        foreach($t_class_names as $class_name)  if (isset($t_pre_defined_class_properties_by_class[$class_name])) $this->t_ignore = array_merge($this->t_ignore,$t_pre_defined_class_properties_by_class[$class_name]);
                    }
                }
                if (isset($conf->t_ignore_properties))
                {
                    $t                      = $conf->t_ignore_properties;               $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_properties_prefix))
                {
                    $t                      = $conf->t_ignore_properties_prefix;        $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
            case 'function_or_class':           // same instance is used for scrambling classes, interfaces, and traits.  and namespaces... and functions ...for aliasing
                $this->case_sensitive       = false;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                $t                          = get_defined_functions();                  $t = array_map('strtolower',$t['internal']);    $t = array_flip($t);
                $this->t_ignore             = array_merge($this->t_ignore,$t);
                if (isset($conf->t_ignore_functions))
                {
                    $t                      = $conf->t_ignore_functions;                $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_functions_prefix))
                {
                    $t                      = $conf->t_ignore_functions_prefix;         $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }

                $this->t_ignore             = array_merge($this->t_ignore, array_flip($this->t_reserved_class_names));
                $this->t_ignore             = array_merge($this->t_ignore, array_flip($this->t_reserved_variable_names));
//                $this->t_ignore             = array_merge($this->t_ignore, array_flip($this->t_reserved_function_names));
                $t                          = get_defined_functions();                  $t = array_flip($t['internal']);
                $this->t_ignore             = array_merge($this->t_ignore,$t);
                if ($conf->t_ignore_pre_defined_classes!='none')
                {
                    if ($conf->t_ignore_pre_defined_classes=='all') $this->t_ignore = array_merge($this->t_ignore,$t_pre_defined_classes);
                    if (is_array($conf->t_ignore_pre_defined_classes))
                    {
                        $t_class_names = array_map('strtolower',$conf->t_ignore_pre_defined_classes);
                        foreach($t_class_names as $class_name)  if (isset($t_pre_defined_classes[$class_name])) $this->t_ignore[$class_name] = 1;
                    }
                }
                if (isset($conf->t_ignore_classes))
                {
                    $t                      = $conf->t_ignore_classes;                  $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_interfaces))
                {
                    $t                      = $conf->t_ignore_interfaces;               $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_traits))
                {
                    $t                      = $conf->t_ignore_traits;                   $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_namespaces))
                {
                    $t                      = $conf->t_ignore_namespaces;               $t = array_map('strtolower',$t);                 $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_classes_prefix))
                {
                    $t                      = $conf->t_ignore_classes_prefix;           $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = array_merge($this->t_ignore_prefix,$t);
                }
                if (isset($conf->t_ignore_interfaces_prefix))
                {
                    $t                      = $conf->t_ignore_interfaces_prefix;        $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = array_merge($this->t_ignore_prefix,$t);
                }
                if (isset($conf->t_ignore_traits_prefix))
                {
                    $t                      = $conf->t_ignore_traits_prefix;            $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = array_merge($this->t_ignore_prefix,$t);
                }
                if (isset($conf->t_ignore_namespaces_prefix))
                {
                    $t                      = $conf->t_ignore_namespaces_prefix;        $t = array_map('strtolower',$t);                 $t = array_flip($t);
                    $this->t_ignore_prefix  = array_merge($this->t_ignore_prefix,$t);
                }
                break;
            case 'method':
                $this->case_sensitive       = false;
                if ($conf->parser_mode=='ONLY_PHP7')    $this->t_ignore = array();      // in php7 method names can be keywords
                else                                    $this->t_ignore = array_flip($this->t_reserved_function_names);
                
                $t                          = array_flip($this->t_reserved_method_names);
                $this->t_ignore             = array_merge($this->t_ignore,$t);
                
                $t                          = get_defined_functions();                  $t = array_map('strtolower',$t['internal']);    $t = array_flip($t);
                $this->t_ignore             = array_merge($this->t_ignore,$t);
                if ($conf->t_ignore_pre_defined_classes!='none')
                {
                    if ($conf->t_ignore_pre_defined_classes=='all') $this->t_ignore = array_merge($this->t_ignore,$t_pre_defined_class_methods);
                    if (is_array($conf->t_ignore_pre_defined_classes))
                    {
                        $t_class_names = array_map('strtolower',$conf->t_ignore_pre_defined_classes);
                        foreach($t_class_names as $class_name)  if (isset($t_pre_defined_class_methods_by_class[$class_name])) $this->t_ignore = array_merge($this->t_ignore,$t_pre_defined_class_methods_by_class[$class_name]);
                    }
                }
                if (isset($conf->t_ignore_methods))
                {
                    $t                      = $conf->t_ignore_methods;                  $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_methods_prefix))
                {
                    $t                      = $conf->t_ignore_methods_prefix;           $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
            case 'label':
                $this->case_sensitive       = true;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                if (isset($conf->t_ignore_labels))
                {
                    $t                      = $conf->t_ignore_labels;                   $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_labels_prefix))
                {
                    $t                      = $conf->t_ignore_labels_prefix;            $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
        }
        if (isset($target_directory))                                   // the constructor will restore previous saved context if exists
        {
            $this->context_directory = $target_directory;
            if (file_exists("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}"))
            {
                $t = unserialize(file_get_contents("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}"));
                if ($t[0] !== self::SCRAMBLER_CONTEXT_VERSION)
                {
                    fprintf(STDERR,"Error:\tContext format has changed! run with --clean option!".PHP_EOL);
                    $this->context_directory = null;        // do not overwrite incoherent values when exiting
                    exit(1);
                }
                $this->t_scramble       = $t[1];
                $this->t_rscramble      = $t[2];
                $this->scramble_length  = $t[3];
                $this->label_counter    = $t[4];
            }
        }
    }

    function __destruct()
    {
        //print_r($this->t_scramble);
        if (!$this->silent) fprintf(STDERR,"Info:\t[%-17s] scrambled \t: %8d%s",$this->scramble_type,count($this->t_scramble),PHP_EOL);
        if (isset($this->context_directory))                            // the destructor will save the current context
        {
            $t      = array();
            $t[0]   = self::SCRAMBLER_CONTEXT_VERSION;
            $t[1]   = $this->t_scramble;
            $t[2]   = $this->t_rscramble;
            $t[3]   = $this->scramble_length;
            $t[4]   = $this->label_counter;
            file_put_contents("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}",serialize($t));
        }
    }

    private function str_scramble($s)                                   // scramble the string according parameters
    {
        $c1         = $this->t_first_chars[mt_rand(0, $this->l1)];      // first char of the identifier
        $c2         = $this->t_chars      [mt_rand(0, $this->l2)];      // prepending salt for md5
        $this->r    = str_shuffle(md5($c2.$s.md5($this->r)));           // 32 chars random hex number derived from $s and lot of pepper and salt

        $s  = $c1;
        switch($this->scramble_mode)
        {
            case 'numeric':
                for($i=0,$l=$this->scramble_length-1;$i<$l;++$i) $s .= $this->t_chars[base_convert(substr($this->r,$i,2),16,10)%($this->l2+1)];
                break;
            case 'hexa':
                for($i=0,$l=$this->scramble_length-1;$i<$l;++$i) $s .= substr($this->r,$i,1);
                break;
            case 'identifier':
            default:
                for($i=0,$l=$this->scramble_length-1;$i<$l;++$i) $s .= $this->t_chars[base_convert(substr($this->r,2*$i,2),16,10)%($this->l2+1)];
        }
        return $s;
    }

    private function case_shuffle($s)   // this function is used to even more obfuscate insensitive names: on each acces to the name, a different randomized case of each letter is used.
    {
        for($i=0;$i<strlen($s);++$i) $s[$i] = mt_rand(0,1) ? strtoupper($s[$i]) : strtolower($s[$i]);
        return $s;
    }

    public function scramble($s)
    {
        $r = $this->case_sensitive ? $s : strtolower($s);
        if ( array_key_exists($r,$this->t_ignore) ) return $s;

        if (isset($this->t_ignore_prefix))
        {
            foreach($this->t_ignore_prefix as $key => $dummy) if (substr($r,0,strlen($key))===$key) return $s;
        }

        if (!isset($this->t_scramble[$r]))      // if not already scrambled:
        {
            for($i=0;$i<50;++$i)                // try at max 50 times if the random generated scrambled string has already beeen generated!
            {
                $x = $this->str_scramble($s);
                $z = strtolower($x);
                $y = $this->case_sensitive ? $x : $z;
                if (isset($this->t_rscramble[$y]) || isset($this->t_ignore[$z]) )           // this random value is either already used or a reserved name
                {
                    if (($i==5) && ($this->scramble_length < $this->scramble_length_max))  ++$this->scramble_length;    // if not found after 5 attempts, increase the length...
                    continue;                                                                                           // the next attempt will always be successfull, unless we already are maxlength
                }
                $this->t_scramble [$r] = $y;
                $this->t_rscramble[$y] = $r;
                break;
            }
            if (!isset($this->t_scramble[$r]))
            {
                fprintf(STDERR,"Scramble Error: Identifier not found after 50 iterations!%sAborting...%s",PHP_EOL,PHP_EOL); // should statistically never occur!
                exit(2);
            }
        }
        return $this->case_sensitive ? $this->t_scramble[$r] : $this->case_shuffle($this->t_scramble[$r]);
    }

    public function unscramble($s)
    {
        if (!$this->case_sensitive) $s = strtolower($s);
        return isset($this->t_rscramble[$s]) ? $this->t_rscramble[$s] : '';
    }
    
    public function generate_label_name($prefix = "!label")
    {
        return $prefix.($this->label_counter++);
    }
}

?>
