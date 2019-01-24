UPGRADE FROM 1.x TO 2.0
=======================

FileSorter
----------

The `FileSorter` class is replaced by `NaturalFileSorter`, the method for sorting changed from `sortNaturally()` to `sort()`.

Use the new `ClosureFileSorter` for simple sort implementations which just need the `SplFileInfo` array or
implement the new `FileSorterInterface` for advanced custom sorters.

Array indizes of sort results are now always reset to ensure consistent results.
The `FileSorter` retains the old behavior, but that class is now deprecated (see above).
