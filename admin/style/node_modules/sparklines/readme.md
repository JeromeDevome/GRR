# Sparkline

[Sparklines](http://en.wikipedia.org/wiki/Sparkline) are tiny line graphs draws inline in the document. They are designed to present the overal trend in data without giving detailed information about a dataset.

![Screenshot of sparklines demo page](https://raw.github.com/mariusGundersen/sparkline/master/demo/screenshot.png)

## Installation

Sparkline can be installed from bower and npm:

```
npm install sparklines
```

or

```
bower install sparklines
```

## Usage

Sparkline can be used as an es6 module, commonJS module, AMD module or as a normal script file. It makes the `Sparkline` constructor available, which takes one or two parameters, an inline element and an options object. The contents of the element is replaced with the sparkline.

```js
var sparkline1 = new Sparkline(document.getElementById("an-inline-element"));
var sparkline2 = new Sparkline($("span")[0], { width: 200 });
```

The returned object has a single method, `draw`, which should be called with an array of `Number`s. This method can be called multiple times to redraw the sparkline.

```js
sparkline1.draw([1, 2, 3, 4, 5]);
sparkline2.draw([0.5, 0.5, 6, 0.5]);
```

[More examples](http://lab.mariusgundersen.net/sparklines/)

## Reference

### Constructor

This is the normal way to create a sparkline instance

### `new Sparkline(HTMLElement, options)`

Returns a sparkline instance. The options object is optional, and overrides the default options if specified. See the section on options further down.

- `HTMLElement`: An HTMLElement, prefereably an inline element, for example a `<span>`.
- `options` (optional): An object with custom options for this sparkline instance.

### Static methods

These are helper methods for creating sparkline instances

#### `Sparkline.init(HTMLElement, options)`

Returns a sparkline instance. This work exactly the same as calling the constructor. The options object is optional, and overrides the default options if specified. See the section on options further down.

- `HTMLElement`: An HTMLElement, prefereably an inline element, for example a `<span>`.
- `options` (optional): An object with custom options for this sparkline instance.

#### `Sparkline.draw(HTMLElement, values, options)`

Draws the values as a sparkline on the given element, with the specified options, before returning the sparkline instance. This is the same as crating a sparkline instance and then calling `draw(values)`.

- `HTMLElement`: An HTMLElement, prefereably an inline element, for example a `<span>`.
- `values`: An array of numbers to be drawn as a sparkline.
- `options` (optional): An object with custom options for this sparkline instance.

#### `Sparkline.options`

An object containing the default options for drawing a sparkline. This is shared by all sparkline instances. Change the values of this object before creating sparkline instances. The options (with default values in parenthesis) available are:

- `width` (`100`): A number giving the width of the sparkline box in pixels.
- `height` (`null`): A number giving the height of the sparkline box in pixels. By default, uses the height of the Canvas element.
- `lineColor` (`"black"`): A string giving the color of the sparkline. Any valid CSS color, including RGB, HEX and HSV.
- `lineWidth` (`1`): A number giving the stroke of the line in pixels.
- `startColor` (`"transparent"`): A string giving the color of the dot marking the first value. Any valid CSS color.
- `endColor` (`"red"`): A string giving the color of the dot marking the last value. Any valid CSS color.
- `maxColor` (`"transparent"`): A string giving the color of the dot marking the highest value. Any valid CSS color.
- `minColor` (`"transparent"`): A string giving the color of the dot marking the lowest value. Any valid CSS color.
- `minValue` (`null`): A number giving the minimum y-axis value. By default, the lowest data value is used.
- `maxValue` (`null`): A number giving the maximum y-axis value. By default, the highest data value is used.
- `dotRadius` (`2.5`): A number giving the size of the dots used to mark important values.
- `tooltip` (`null`): A function that takes three arguments (`value`, `index`, `array`) and returns a tooltip string to show when the user hovers over the sparkline. By default there is no tooltip.

### Instance methods

#### `sparkline.draw(values)`

Draws the values as a sparkline in the element given in the constructor. This method can be called multiple times, and will replace the previous sparkline with a new one.

- `values`: An array of numbers to be drawn as a sparkline

## License

Copyright (C) 2013 Marius Gundersen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
