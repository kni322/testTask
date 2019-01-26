<?php
/*
 * main.php - Основной файл, содержит текущую игру.
 * classes.php - Файл с классами.
 * function.php - Файл с функциями.
 *
 * Человек всегда играет за "О", бот всегда за "X".
 * Порядок ходов определяется в классе игры.
 * */
require "classes.php";

$stdin = fopen("php://stdin", "r");
$game = new game();

//Основная игра, пока не выполнется условие победы
while(!$game->endGame()){

    if($game->curr_player == $game->human)
        $game->turn($game->curr_player);
    else {
        if($game->field->data[4] != 4) {                                        //Оптимальный ход это самый центр поля, можно не запускать рекурсию, если это поле свободно.
            $lastTurn = $game->lastTurn();

            if(!$lastTurn) {                                                    //Проверка может ли игра завершится в этот ход бота или в след. ход человека (тогда нужно защищаться)
                $index = $game->miniMax($game->field->data, $game->ai);         //Иначе Минимакс'ом ищем оптимальный ход.
                $game->field->put($game->ai, $index->index);                    //И ставим "X" в эту ячейку.
            }else
                $game->field->put($game->ai, $lastTurn);                        //Либо блокируем победу человека, либо завершаем игру сами.
        }else{
            $game->field->put($game->ai, 4);
        }
    }

    if(count($game->countFree($game->field->data)) == 0) {                  //Если свободных ячеек нет - ничья.
        print("\e[5;31mDRAW\e[0m\n");
        $game->field->draw();
        exit;
    }
    $game->swapTurn();                                                      //Текущий и предыдущий игрок меняются

}


print("\n".$game->winner);
$game->field->draw();
