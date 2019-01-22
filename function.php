
<?php
/*
* Файл для функций.
* */

//определение является ли символ цифрой от 0 до 9
function isD($item){
    if(!is_int($item))
        return false;
    if($item>=0 && $item<9)
        return true;
    else
        return false;
}
