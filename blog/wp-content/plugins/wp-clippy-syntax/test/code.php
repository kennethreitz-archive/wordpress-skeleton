<?php

$code = array();
$code['php'] = <<<EOF
<div id="foo">
<?php
  function foo() {
    echo "Hello World!\\n";
  }
  for (\$i = 0; \$i < 10 $i++) {
    foo();
  }
?>
</div>
EOF;

$code['lisp'] = <<<EOF
(defun foo
  "bleh *bleh* bleh"
  (interactive))
EOF;

$code['java'] = <<<EOF
public class Hello {
  public static void main(String[] args) {
    System.out.println("Hello World!");
  }
}
EOF;

$code['xml'] = <<<EOF
<xml>
	<foo>
		<bar id="howdy">&quot;Hello World!&quot;</bar>
	</foo>
</xml>
EOF;

$code['html'] = <<<EOF
<html><head><title>Hello World</title></head>
  <body>
    <h1>Hello World!</h1>
    <p><strong>howdy</strong></p>
  </body>
</html>
EOF;

$code['ruby'] = <<<EOF
class Example
  def example(arg1)
    return "Hello: " + arg1.to_s
  end
end
EOF;

$code['rails'] = <<<EOF
ActionController::Routing::Routes.draw do |map|
   map.connect ':controller/:action', :action => 'index', :requirements => { :action => /(?:[a-z](?:[-_]?[a-z]+)*)/ }
   map.connect ':controller/:id',     :action => 'show',  :requirements => { :id     => /\d+/                       }
   map.connect ':controller/:id/:action',
end
EOF;

$code['ocaml'] = <<<EOF
let square x = x * x;;
val square : int -> int =
let rec fact x =
  if x < = 1 then 1 else x * fact (x - 1);;
val fact : int -> int =
fact 5;; - : int = 120
square 120;; - : int = 14400
EOF;

$code['python'] = <<<EOF
from itertools import islice

def fib():
    x, y = 1, 1
    while True:
        yield x
        x, y = y, x + y

for num in islice(fib(), 20):
    print num
EOF;

$code['c'] = <<<EOF
_tcsncat_s(CurrentFileName, MAX_PATH, TEXT("\\\\"), MAX_PATH);
_tcsncat_s(CurrentFileName, MAX_PATH, FileInformation.cFileName, MAX_PATH);

if(FileInformation.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY)
{
    RecurseFileSystem(CurrentFileName);
}
else
{
    /* Do action on file here! */
}
EOF;

?>
