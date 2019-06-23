<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait CompilesErrors
{
    /**
     * Compile the error statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileError($expression)
    {
        i$expression = explode(',', $this->stripParentheses($expression));
        $bag = Arr::get($expression, 0, 'default');
        $attribute = trim(Arr::get($expression, 1), $expression);

        return '<?php if ($errors->getBag('.$bag.')->has('.$attribute.')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->getBag('.$bag.')->first('.$attribute.'); ?>';
    }

    /**
     * Compile the enderror statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEnderror($expression)
    {
        return '<?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>';
    }
}
