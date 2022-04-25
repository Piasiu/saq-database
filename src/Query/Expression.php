<?php
namespace Saq\Database\Query;

use Stringable;

class Expression implements Stringable
{
    private string $content;

    /**
     * @param string $content
     * @param bool $parenthesesEnclose
     */
    public function __construct(string $content, bool $parenthesesEnclose = false)
    {
        if ($parenthesesEnclose)
        {
            $this->content = '('.$content.')';
        }
        else
        {
            $this->content = $content;
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->content;
    }
}