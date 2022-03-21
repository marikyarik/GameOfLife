<?php

class Cell
{

    private int $x;
    private int $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY(): int
    {
        return $this->y;
    }
}

class Task {

    private string $action;
    private Cell $cell;

    public function __construct(string $action, Cell $cell)
    {
        $this->action = $action;
        $this->cell = $cell;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return Cell
     */
    public function getCell(): Cell
    {
        return $this->cell;
    }
}

class Repository
{

    private array $cells = [];
    private array $queue = [];

    public function find(int $x, int $y): ?Cell
    {
        return $this->cells[$x][$y] ?? null;
    }

    public function addCell(Cell $cell): void
    {
        $task = new Task('add', $cell);
        $this->queue[] = $task;
    }

    public function deleteCell(Cell $cell): void
    {
        $task = new Task('delete', $cell);
        $this->queue[] = $task;
    }

    public function flush(): void
    {
        foreach ($this->queue as $task) {
            if ($task->getAction() === 'add'){
                $this->cells[$task->getCell()->getX()][$task->getCell()->getY()] = $task->getCell();
            } else if ($task->getAction() === 'delete'){
                if (isset($this->cells[$task->getCell()->getX()][$task->getCell()->getY()])) {
                    unset($this->cells[$task->getCell()->getX()][$task->getCell()->getY()]);
                }
            }
        }
        $this->queue = [];
    }

    public function getCount(): int
    {
        $count = 0;

        foreach ($this->getAll() as $cell) {
            $count++;
        }

        return $count;
    }

    public function getAll(): Iterator
    {
        foreach ($this->cells as $cells) {
            foreach ($cells as $cell) {
                yield $cell;
            }
        }
    }
}

class GameOfLife
{
    private int $currentGeneration = 1;
    private int $width = 25;
    private int $height = 25;

    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function getCurrentGeneration(): int
    {
        return $this->currentGeneration;
    }

    public function setArea(int $width, int $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function init(): void
    {
        $this->addCell(0, 1);
        $this->addCell(1, 0);
        $this->addCell(-1, -1);
        $this->addCell(0, -1);
        $this->addCell(1, -1);
    }

    private function addCell(int $x, int $y): void
    {
        $x = floor($this->width / 2) + $x;
        $y = floor($this->height / 2) + $y;

        $cell = new Cell($x, $y);
        $this->repository->addCell($cell);
        $this->repository->flush();
    }

    public function isAlive(): bool
    {
        return $this->repository->getCount() > 0;
    }

    public function tick(): void
    {
        $this->currentGeneration++;

        foreach ($this->repository->getAll() as $cell) {
            [$countAlive, $deadCellsCoords] = $this->countAliveByCoord($cell->getX(), $cell->getY());

            if ($countAlive < 2 || $countAlive > 3) {
                $this->repository->deleteCell($cell);
            }

            foreach ($deadCellsCoords as $coords) {
                [$countAlive] = $this->countAliveByCoord($coords['x'], $coords['y']);

                if ($countAlive === 3) {

                    if ($coords['x'] >= 0 && $coords['x'] < $this->width && $coords['y'] >= 0 && $coords['y'] < $this->height) {
                        $newCell = new Cell($coords['x'], $coords['y']);
                        $this->repository->addCell($newCell);
                    }

                }
            }
        }

        $this->repository->flush();
    }

    private function countAliveByCoord(int $x, int $y): array {
        $neighborsCoords = $this->getNeighborsByCoord($x, $y);

        $countAlive = 0;
        $deadCellsCoords = [];
        foreach ($neighborsCoords as $coord) {
            $aliveCell = $this->repository->find($coord['x'], $coord['y']);
            if (null !== $aliveCell) {
                $countAlive++;
                continue;
            }

            $deadCellsCoords[] = $coord;
        }

        return [$countAlive, $deadCellsCoords];
    }

    private function getNeighborsByCoord(int $x, int $y): array
    {
        $neighborsKeys = [];

        $neighborsKeys[] = ['x' => $x - 1, 'y' => $y];
        $neighborsKeys[] = ['x' => $x - 1, 'y' => $y - 1];
        $neighborsKeys[] = ['x' => $x - 1, 'y' => $y + 1];
        $neighborsKeys[] = ['x' => $x, 'y' => $y - 1];
        $neighborsKeys[] = ['x' => $x, 'y' => $y + 1];
        $neighborsKeys[] = ['x' => $x + 1, 'y' => $y];
        $neighborsKeys[] = ['x' => $x + 1, 'y' => $y - 1];
        $neighborsKeys[] = ['x' => $x + 1, 'y' => $y + 1];

        return $neighborsKeys;
    }

    public function print(): void
    {
        echo "Generation: " . $this->currentGeneration . PHP_EOL;

        $array = array_fill(0, $this->height, array_fill(0, $this->width, '##'));

        foreach ($this->repository->getAll() as $cell) {
            $array[$cell->getY()][$cell->getX()] = '00';
        }

        foreach (array_reverse($array) as $i) {
            echo PHP_EOL;
            foreach ($i as $cell) {
                echo $cell . ' ';
            }
        }
        echo PHP_EOL;
    }

}

$repository = new Repository();

$game = new GameOfLife($repository);

//$game->setArea(5, 5);
$game->init();
$game->print();

while ($game->isAlive()) {
    sleep(1);
    $game->tick();
    $game->print();
}

echo 'All cells are dead. Last generation was: ' . $game->getCurrentGeneration() . PHP_EOL;
