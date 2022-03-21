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

    public function getId(): string
    {
        return "{$this->x}|{$this->y}";
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

class Repository
{

    private array $cells = [];

    public function addCell(Cell $cell): void
    {
        if (!array_key_exists($cell->getId(), $this->cells)) {
            $this->cells[$cell->getId()] = $cell;
        }
    }

    public function deleteCell(Cell $cell): void
    {
        if (array_key_exists($cell->getId(), $this->cells)) {
            unset($this->cells[$cell->getId()]);
        }
    }

    public function getCount(): int
    {
        return sizeof($this->cells);
    }

    public function getAll(): array
    {
        return $this->cells;
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
    }

    public function isAlive(): bool
    {
        return $this->repository->getCount() > 0;
    }

    public function tick(): void
    {
        $this->currentGeneration++;

        $cells = $this->repository->getAll();
        foreach ($cells as $cell) {
            $neighborsKey = $this->getNeighborsByKey($cell->getId());

            $aliveCellKey = array_intersect($neighborsKey, array_keys($cells));
            $deadCellsKey = array_diff($neighborsKey, $aliveCellKey);

            $countAlive = count($aliveCellKey);
            if ($countAlive < 2 || $countAlive > 3) {
                $this->repository->deleteCell($cell);
            }

            foreach ($deadCellsKey as $key) {
                $neighborsKey = $this->getNeighborsByKey($key);
                $countAlive = count(array_intersect($neighborsKey, array_keys($cells)));
                if ($countAlive === 3) {
                    $coordinates = $this->getCoordinatesByKey($key);

                    if($coordinates['x'] >= 0 && $coordinates['x'] < $this->width && $coordinates['y'] >= 0 && $coordinates['y'] < $this->height) {
                        $newCell = new Cell($coordinates['x'], $coordinates['y']);
                        $this->repository->addCell($newCell);
                    }

                }
            }
        }
    }

    private function getCoordinatesByKey(string $key): array
    {
        $coordinates = explode('|', $key);
        return ['x' => $coordinates[0], 'y' => $coordinates[1]];
    }

    private function getNeighborsByKey(string $key): array
    {
        $coordinates = $this->getCoordinatesByKey($key);

        $neighborsKeys = [];
        $x = $coordinates['x'];
        $y = $coordinates['y'];

        $neighborsKeys[] = ($x - 1) . "|" . $y;
        $neighborsKeys[] = ($x - 1) . "|" . ($y - 1);
        $neighborsKeys[] = ($x - 1) . "|" . ($y + 1);
        $neighborsKeys[] = $x . "|" . ($y - 1);
        $neighborsKeys[] = $x . "|" . ($y + 1);
        $neighborsKeys[] = ($x + 1) . "|" . $y;
        $neighborsKeys[] = ($x + 1) . "|" . ($y - 1);
        $neighborsKeys[] = ($x + 1) . "|" . ($y + 1);

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

$game->init();
$game->print();

while ($game->isAlive()) {
    sleep(1);
    $game->tick();
    $game->print();
}

echo 'All cells are dead. Last generation was: '. $game->getCurrentGeneration() . PHP_EOL;
