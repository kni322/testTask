<?php
/*
 * Файл с классами.
 * "game" - основной класс игры.
 * "field" - класс поля на котором ведется игра.
 * "variable" - класс для хранения значений поиска Минимакс'a.
 */
require "function.php";
class game{


    public $field;          //Поле игры, представляет собой массив на 9 элементов, если клетка пустая то в ней хранится ее индекс
    public $human = 'O';
    public $ai = 'X';
    public $first_turn;     //Переменная для хранения того кто первый ходит
    public $curr_player;    //Переменная для хранения текущего хода
    public $last_player;    //Переменная для хранения предыдущего хода
    public $winner;         //Победитель

    public function __construct()
    {
        global $stdin;

        $this->field = new field();
        print("\x1b[5;33mCHOOSE FIRST TURN: X OR O\n");
        fscanf($stdin, "%c\n", $char);

        if($char != 'X' && $char !='O'){
            print("Wrong symbol\nTry again\n\n");
            $this->__construct();
        }


        $this->first_turn = $char;
        $this->curr_player = $this->first_turn;

        if($this->curr_player == "O")
            $this->last_player = "X";
        else
            $this->last_player = "O";

    }
    /*
     * Основной алгоритм, в нем рекурсивно минимизируются потери от всех возможных ходов.
     * Победа приносит +10 очков, поражение -10, ничья 0.
     * Алгоритм рассматривает все возможные исходы, а затем выбирает где в сумме больше очков.
     */
    public function miniMax($nField, $player){
        //Получаем индексы свободных ячееек
        $avFree = $this->countFree($nField);
        //Подсчет очков
        if($this->win($nField, $this->ai))
            return 10;
        if($this->win($nField, $this->human))
            return -10;
        if (!$avFree)
            return 0;
        //Перебор всех вариантов. variables - хранит все возможные варианты
        $variables = array();
        // Перебираем свободные ячейки и с них запускаем Минимакс
        for($i = 0; $i <count($avFree); $i++){
            $variable = new variable();
            $variable->index = $nField[$avFree[$i]];
            //Ход за игрока
            $nField[$avFree[$i]] = $player;
            //Получаем очки с помощью Минимакса'а 
            if($player == $this->ai){
                $variable->score = $this->miniMax($nField, $this->human);
            }else{
                $variable->score = $this->miniMax($nField, $this->ai);
            }
            //очищаем ячейку
            $nField[$avFree[$i]] = $variable->index;
            //добавляем вариант
            array_push($variables, $variable);
        }

        $score = PHP_INT_MIN;
        /*
         * Ищем лучшие варианты, для Бота лучший вариант где больше очков.
         * Если же ход человека, то лучший вариант где меньше очков
         */
        if($player == $this->ai){
            for($i = 0; $i < count($variables); $i++){
                if($variables[$i]->score > $score){
                    $score = $variables[$i]->score;
                    $best = $i;
                }
            }
        }else{
            $score = PHP_INT_MAX;

            for($i = 0; $i < count($variables); $i++){
                if($variables[$i]->score < $score){
                    $score = $variables[$i]->score;
                    $best = $i;
                }
            }
        }
        return $variables[$best];
    }
    //подсчет свободных клеток
    public function countFree($field){

        $tmp = array();

        foreach ($field as $item){
            if(isD($item))
                array_push($tmp, $item);
        }

        return $tmp;

    }
    //Есть ли победная комбинация от $player
    function win($field, $player){
        if (
            ($field[0] === $player && $field[1] === $player && $field[2] === $player) ||
            ($field[3] === $player && $field[4] === $player && $field[5] === $player) ||
            ($field[6] === $player && $field[7] === $player && $field[8] === $player) ||
            ($field[0] === $player && $field[3] === $player && $field[6] === $player) ||
            ($field[1] === $player && $field[4] === $player && $field[7] === $player) ||
            ($field[2] === $player && $field[5] === $player && $field[8] === $player) ||
            ($field[0] === $player && $field[4] === $player && $field[8] === $player) ||
            ($field[2] === $player && $field[4] === $player && $field[6] === $player)
        )
            return true;
        else
            return false;
    }
    //Функция определения конца игры.
    public function endGame(){
        if($this->win($this->field->data, $this->ai)){
            $this->winner = "\x1b[5;31mYou DEFEAT!\n\x1b[0m";
            return true;
        }
        if($this->win($this->field->data, $this->human)){
            $this->winner = "\x1b[5;32mYou WIN!\n\x1b[0m";
            return true;
        }
        return false;
    }
    //Ход человека
    public function turn($char){
        global $stdin;

        print("\n\x1b[5;1;34mSelect cell\n\x1b[0m");
        $this->field->draw();
        print("\n");

        fscanf($stdin, "%d\n", $pos);

        if($this->field->put($char,$pos)){
            return true;
        }else{
            print("Wrong symbol\nTry again\n");
            $this->turn($char);
        }

    }
    //Проверка может ли игра завершиться в этот ход бота, либо в следущий ход человека
    public function lastTurn(){
        //Найдем все свободные ячейки
        $avFree = $this->countFree($this->field->data);
        /*
         * По очереди будет проверять 2 условия:
         *  1) бот может победить сейчас
         *  2) человек победит в следущий ход и нужно заблокировать его
         *  3) если 2 условия не выполняются - вернем False
         *
         * Проверять будем просто подстановкой так как сложность O(n)
         *
         * После каждой подстановки будем стирать значение
         */
        foreach ($avFree as $item){
            $this->field->data[$item] = $this->ai;

            if($this->endGame()){
                $this->field->data[$item] = $item;

                return $item;
            }else{
                $this->field->data[$item] = $this->human;

                if($this->endGame()){
                    $this->field->data[$item] = $item;

                    return $item;
                }
            }
            $this->field->data[$item] = $item;
        }
        return false;
    }
    //Смена хода
    public function swapTurn(){
        $t = $this->curr_player;
        $this->curr_player = $this->last_player;
        $this->last_player = $t;
    }

}

class field{

    public $data;           // основное поле игры

    public function __construct()
    {
        $this->data = range(0, 8);
    }
    //отрисовка поля в терминале
    public function draw(){
        print ("------\n");
        for($i = 0; $i < 9; $i++){
            if(isD($this->data[$i]))
                print("\x1b[1;30m");
            else
                if($this->data[$i]=="X")
                    print("\x1b[1;31m");
                else
                    print("\x1b[1;32m");
            print($this->data[$i]."\x1b[0m|");
            if($i == 2 || $i == 5 || $i == 8)
                print("\n------\n");
        }
    }
    //постановка символа в основне поле
    public function put($char, $pos){
        if(isD($pos) && isD($this->data[$pos])){
            $this->data[$pos] = $char;
            return true;
        }else{
            return false;
        }
    }
}

class variable{
    public $index;
    public $score;
}
