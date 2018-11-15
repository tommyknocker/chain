## The Chain.

**Library that chains every class without modifying it.**

### Examples

```
use Tommyknocker/Chain/Chain;

Chain::YourObject('param1','param2)
    ->currentObjectMethod('param2')
    ->change('SomeOtherObjects', $args)
    ->otherObjectMethod();
    
//To access current method result you may use:
Chain::YourObject('param1','param2)->result;
  
//To access current object instance you may use
Chain::YourObject('param1','param2)->instance;      
```

### Plans

* Use external DI
* Test library with different namespace objects
