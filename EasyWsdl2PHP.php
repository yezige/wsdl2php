<?php
    class EasyWsdl2PHP
    {

        static public function generate($url,$sname)
        {
            $soapClient       = new SoapClient($url);
            $classesArr = array();

            $functions = $soapClient->__getFunctions();

            $nl="\n";
            $tab1 = '    ';
            $tab2 = $tab1.$tab1;

            $code ='';
            $simpletypes = array('string','int','double','dateTime','float');
            foreach($functions as $func)
            {
                $temp = explode(' ' ,$func,2);
                //less process whateever is inside ()
                $start = strpos($temp[1],'(');
                $end = strpos($temp[1],'(');
                $parameters = substr($temp[1],$start,$end);


                $t1 = str_replace(')','',$temp[1]);
                $t1 = str_replace('(',':',$t1);
                $t2 = explode(':',$t1);
                $func = $t2[0];
                $par = $t2[1];

                $params = explode(' ', $par);
                $p1 = '$' . $params[0];


                $code .= $nl . $tab1 . 'function ' . $func . '(' . $p1 .')'
                . "{$nl}{$tab1}{";
                if ($temp[0] == 'void')
                    $code .= $nl . $tab2 . "\$this->soapClient->$func({$p1});{$nl}{$tab1}}";
                else
                {
                    $code .= $nl . $tab2 . '$' . $temp[0] . ' = ' .  "\$this->soapClient->$func({$p1});";
                    $code .= $nl . $tab2 . "return \${$temp[0]};{$nl}{$tab1}}";
                }


            }
            $code .= "{$nl}}{$nl}";


            //    print_r($functions);
            //    echo "<hr>";
            $types = $soapClient->__getTypes();
            // print_r($types);
            $codeType ='';
            foreach ($types as $type)
            {
                if (substr($type,0,6) == 'struct')
                {
                    $data = trim(str_replace(array('{','}'),'',substr($type,strpos($type, '{')+1)));
                    $data_members = explode(';',$data);
                    //print_r($data_members);
                    // echo "[" . $data . "]";
                    $classname = trim(substr($type,6,strpos($type,'{')-6));

                    //write object
                    $codeType .= $nl . 'class ' . $classname .'{';
                    $classesArr [] = $classname;
                    foreach($data_members as $member)
                    {
                        $member = trim($member);
                        if (strlen($member)< 1) continue;
                        list($data_type,$member_name) = explode(' ' , $member);
                        $codeType .= "{$nl}{$tab1}var \${$member_name};//{$data_type}";
                    }

                    $codeType .= $nl . '}';

                }
            }

            $mapstr = $nl . $tab1 . 'private static $classmap = array(';
            $classMAPCode = array();
            foreach($classesArr as $cname)
            {
                // $mapstr .= "\n,'$cname'=>'$cname'";
                $classMAPCode[] = "{$nl}{$tab2}'$cname' => '$cname'";
            }
            //print_r($classMAPCode);
            $mapstr .= implode (',',$classMAPCode);
            $mapstr .= "{$nl}{$tab1});";

            $fullcode = <<< EOT
<?php
$codeType
class $sname $nl{
    var \$soapClient;
    $mapstr

    function __construct(\$url='{$url}')
    {
        \$this->soapClient = new SoapClient(\$url,array("classmap" => self::\$classmap,"trace" => true,"exceptions" => true));
    }
    $code
?>
EOT;

            return $fullcode;
        }

    }
?>
