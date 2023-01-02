<?php

namespace Trello\Api\Board;

use Trello\Api\AbstractApi;
use Trello\Exception\InvalidArgumentException;

/**
 * Trello Board Labels API
 * @link https://trello.com/docs/api/board
 *
 * Fully implemented.
 */
class Labels extends AbstractApi
{
    private const SUPPORTED_COLORS = [
        "green_light",
        "green_dark",
        "green",
        "yellow_light",
        "yellow_dark",
        "yellow",
        "orange_light",
        "orange_dark",
        "orange",
        "red_light",
        "red_dark",
        "red",
        "purple_light",
        "purple_dark",
        "purple",
        "blue_light",
        "blue_dark",
        "blue",
        "sky_light",
        "sky_dark",
        "sky",
        "lime_light",
        "lime_dark",
        "lime",
        "pink_light",
        "pink_dark",
        "pink",
        "black_light",
        "black_dark",
        "black",
        "notset",
    ];

    /**
     * Base path of board labels api
     * @var string
     */
    protected $path = 'boards/#id#/labels';

    /**
     * Get labels related to a given board
     * @link https://trello.com/docs/api/board/#get-1-boards-board-id-labels
     *
     * @param string $id the board's
     * @param array $params optional parameters
     *
     * @return array
     */
    public function all($id, array $params = [])
    {
        return $this->get($this->getPath($id), $params);
    }

    /**
     * Get a label related to a given board
     * @link https://trello.com/docs/api/board/#get-1-boards-board-id-labels-idlabel
     *
     * @param string $id the board's id
     * @param string $color the label's color
     *
     * @return array
     */
    public function show($id, $color)
    {
        if (!in_array($color, self::SUPPORTED_COLORS)) {
            throw new InvalidArgumentException(sprintf(
                'The "color" parameter must be one of "%s".',
                implode(", ", self::SUPPORTED_COLORS)
            ));
        }

        return $this->get($this->getPath($id) . '/' . rawurlencode($color));
    }

    /**
     * Add a label related to a given board
     * @link https://developers.trello.com/advanced-reference/board#post-1-boards-board-id-labels
     *
     * @param string $id    the board's id
     * @param string $color the label's color
     * @param string $name  the label's name
     *
     * @return array
     */
    public function add($id, $color, $name)
    {
       
         $params = array(
             'color' => $color,
             'name' => $name
         );
        
        return $this->post($this->getPath($id), $params);
    }
    
    /**
     * Set a label's name on a given board and for a given color
     * @link https://trello.com/docs/api/board/#put-1-boards-board-id-labelnames-blue
     * @link https://trello.com/docs/api/board/#put-1-boards-board-id-labelnames-green
     * @link https://trello.com/docs/api/board/#put-1-boards-board-id-labelnames-orange
     * @link https://trello.com/docs/api/board/#put-1-boards-board-id-labelnames-purple
     * @link https://trello.com/docs/api/board/#put-1-boards-board-id-labelnames-red
     * @link https://trello.com/docs/api/board/#put-1-boards-board-id-labelnames-yellow
     *
     * @param string $id the board's id
     * @param string $color the label color to set the name of
     * @param string $name
     *
     * @return array
     */
    public function setName($id, $color, $name)
    {
        if (!in_array($color, self::SUPPORTED_COLORS)) {
            throw new InvalidArgumentException(sprintf(
                'The "color" parameter must be one of "%s".',
                implode(", ", self::SUPPORTED_COLORS)
            ));
        }

        return $this->put('boards/' . rawurlencode($id) . '/labelNames/' . rawurlencode($color), ['value' => $name]);
    }

    public function update($id, $color, $name)
    {
        if (!in_array($color, self::SUPPORTED_COLORS)) {
            throw new InvalidArgumentException(sprintf(
                'The "color" parameter must be one of "%s".',
                implode(", ", self::SUPPORTED_COLORS)
            ));
        }

        return $this->put('labels/' . rawurlencode($id), ['name' => $name, 'color' => $color]);
    }

    public function updateName($id, $name)
    {
        return $this->put('labels/' . rawurlencode($id), ['name' => $name]);
    }

    public function updateColor($id, $color)
    {
        if (!in_array($color, self::SUPPORTED_COLORS)) {
            throw new InvalidArgumentException(sprintf(
                'The "color" parameter must be one of "%s".',
                implode(", ", self::SUPPORTED_COLORS)
            ));
        }

        return $this->put('labels/' . rawurlencode($id), ['color' => $color]);
    }
}
