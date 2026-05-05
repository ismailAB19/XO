<?php
session_start();

// Initialize game board
if (!isset($_SESSION['board'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['game_over'] = false;
    $_SESSION['winner'] = null;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'move') {
        $position = intval($_POST['position']);

        // Player move
        if ($_SESSION['board'][$position] === '') {
            $_SESSION['board'][$position] = 'X';

            // Check if player won
            $winner = checkWinner($_SESSION['board']);
            if ($winner === 'X') {
                $_SESSION['game_over'] = true;
                $_SESSION['winner'] = 'X';
                echo json_encode(['board' => $_SESSION['board'], 'game_over' => true, 'winner' => 'X']);
                exit;
            }

            // Check for draw
            if (isBoardFull($_SESSION['board'])) {
                $_SESSION['game_over'] = true;
                $_SESSION['winner'] = 'draw';
                echo json_encode(['board' => $_SESSION['board'], 'game_over' => true, 'winner' => 'draw']);
                exit;
            }

            // AI move
            $best_move = findBestMove($_SESSION['board']);
            $_SESSION['board'][$best_move] = 'O';

            // Check if AI won
            $winner = checkWinner($_SESSION['board']);
            if ($winner === 'O') {
                $_SESSION['game_over'] = true;
                $_SESSION['winner'] = 'O';
                echo json_encode(['board' => $_SESSION['board'], 'game_over' => true, 'winner' => 'O']);
                exit;
            }

            // Check for draw
            if (isBoardFull($_SESSION['board'])) {
                $_SESSION['game_over'] = true;
                $_SESSION['winner'] = 'draw';
                echo json_encode(['board' => $_SESSION['board'], 'game_over' => true, 'winner' => 'draw']);
                exit;
            }

            echo json_encode(['board' => $_SESSION['board'], 'game_over' => false, 'winner' => null]);
            exit;
        }
    } elseif ($_POST['action'] === 'reset') {
        $_SESSION['board'] = array_fill(0, 9, '');
        $_SESSION['game_over'] = false;
        $_SESSION['winner'] = null;
        echo json_encode(['board' => $_SESSION['board'], 'game_over' => false, 'winner' => null]);
        exit;
    }
}

function checkWinner($board)
{
    $win_conditions = [
        [0, 1, 2],
        [3, 4, 5],
        [6, 7, 8],
        [0, 3, 6],
        [1, 4, 7],
        [2, 5, 8],
        [0, 4, 8],
        [2, 4, 6]
    ];

    foreach ($win_conditions as $condition) {
        if (
            $board[$condition[0]] !== '' &&
            $board[$condition[0]] === $board[$condition[1]] &&
            $board[$condition[1]] === $board[$condition[2]]
        ) {
            return $board[$condition[0]];
        }
    }
    return null;
}

function isBoardFull($board)
{
    return !in_array('', $board);
}

function minimax($board, $depth, $is_maximizing)
{
    $winner = checkWinner($board);

    if ($winner === 'O') return 10 - $depth;
    if ($winner === 'X') return $depth - 10;
    if (isBoardFull($board)) return 0;

    if ($is_maximizing) {
        $best_score = -PHP_INT_MAX;
        for ($i = 0; $i < 9; $i++) {
            if ($board[$i] === '') {
                $board[$i] = 'O';
                $score = minimax($board, $depth + 1, false);
                $board[$i] = '';
                $best_score = max($best_score, $score);
            }
        }
        return $best_score;
    } else {
        $best_score = PHP_INT_MAX;
        for ($i = 0; $i < 9; $i++) {
            if ($board[$i] === '') {
                $board[$i] = 'X';
                $score = minimax($board, $depth + 1, true);
                $board[$i] = '';
                $best_score = min($best_score, $score);
            }
        }
        return $best_score;
    }
}

function findBestMove($board)
{
    $best_score = -PHP_INT_MAX;
    $best_move = -1;

    for ($i = 0; $i < 9; $i++) {
        if ($board[$i] === '') {
            $board[$i] = 'O';
            $score = minimax($board, 0, false);
            $board[$i] = '';

            if ($score > $best_score) {
                $best_score = $score;
                $best_move = $i;
            }
        }
    }
    return $best_move;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- bootstrap link  -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <title>Tic Tac Toe Game</title>
    <style>
        .game-cell {
            cursor: pointer;
            font-size: 2em;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }

        .game-cell:hover {
            background-color: #f0f0f0;
        }

        .game-cell.x {
            color: blue;
        }

        .game-cell.o {
            color: red;
        }
    </style>
</head>

<body>
    <h1 class="display-4 text-center text-success mt-4">Tic Tac Toe Game</h1>
    <p class="text-center text-info">Play as X, AI plays as O</p>
    <div class="container text-center mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div id="game-status" class="alert alert-info mb-4">Your turn! (X)</div>
                <div class="row no-gutters" id="board" style="border: 3px solid black; width: 100%; margin: 0 auto;">
                    <?php for ($i = 0; $i < 9; $i++): ?>
                        <div class="game-cell col-4 <?php echo $_SESSION['board'][$i] === 'X' ? 'x' : ($_SESSION['board'][$i] === 'O' ? 'o' : ''); ?>"
                            data-index="<?php echo $i; ?>"
                            style="border-right: <?php echo ($i % 3) !== 2 ? '2px solid black' : '0'; ?>; border-bottom: <?php echo $i < 6 ? '2px solid black' : '0'; ?>; min-height: 100px;">
                            <?php echo $_SESSION['board'][$i]; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <button id="reset-btn" class="btn btn-primary mt-4">New Game</button>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cells = document.querySelectorAll('.game-cell');
            const gameStatus = document.getElementById('game-status');
            const resetBtn = document.getElementById('reset-btn');

            cells.forEach(cell => {
                cell.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    const currentText = this.textContent.trim();

                    // Only allow move if cell is empty and game is not over
                    if (currentText === '' && gameStatus.textContent !== 'Game Over!') {
                        makeMove(index);
                    }
                });
            });

            resetBtn.addEventListener('click', function() {
                resetGame();
            });

            function makeMove(index) {
                const formData = new FormData();
                formData.append('action', 'move');
                formData.append('position', index);

                fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        updateBoard(data.board);

                        if (data.game_over) {
                            if (data.winner === 'X') {
                                gameStatus.textContent = '🎉 You Won!';
                                gameStatus.className = 'alert alert-success';
                            } else if (data.winner === 'O') {
                                gameStatus.textContent = '😢 AI Won!';
                                gameStatus.className = 'alert alert-danger';
                            } else {
                                gameStatus.textContent = '🤝 Draw!';
                                gameStatus.className = 'alert alert-warning';
                            }
                            gameStatus.textContent += ' - Game Over!';
                        } else {
                            gameStatus.textContent = "AI's turn...";
                            gameStatus.className = 'alert alert-info';
                            setTimeout(() => {
                                gameStatus.textContent = "Your turn! (X)";
                            }, 500);
                        }
                    });
            }

            function updateBoard(board) {
                cells.forEach((cell, index) => {
                    cell.textContent = board[index];
                    cell.classList.remove('x', 'o');
                    if (board[index] === 'X') {
                        cell.classList.add('x');
                    } else if (board[index] === 'O') {
                        cell.classList.add('o');
                    }
                });
            }

            function resetGame() {
                const formData = new FormData();
                formData.append('action', 'reset');

                fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        updateBoard(data.board);
                        gameStatus.textContent = 'Your turn! (X)';
                        gameStatus.className = 'alert alert-info';
                    });
            }
        });
    </script>
</body>

</html>