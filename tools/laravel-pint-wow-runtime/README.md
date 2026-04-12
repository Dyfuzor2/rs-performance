# Laravel Pint WOW (agent-local, April 2026+)

**Tak, ma sens:** Pint to oficjalny formatter Laravela; ten skrypt używa **tej samej** logiki PHP co `laravel-boost-mcp-runtime` (PATH + WinGet + `php.path`), więc działa w Cursorze i w terminalu tak samo.

## Run

```powershell
# Zmienione pliki (zgodnie z wytycznymi Boost)
G:\gravity\tools\laravel-pint-wow-runtime\run-pint.ps1 --dirty

# Pełny projekt (ostrożnie na dużych drzewach)
G:\gravity\tools\laravel-pint-wow-runtime\run-pint.ps1
```

## Kiedy używać

- Po edycji **PHP** w `G:\gravity` przed commitem.
- Agent może wołać ten skrypt zamiast polegać na losowym `php` w PATH Cursora.

## Zależności

- `vendor/bin/pint` (Composer dev dependency).
- PHP 8.5+ jak reszta stacku.
- **Rozszerzenia:** Pint wymaga **mbstring**. `run-pint.ps1` wywołuje **`Ensure-GravityPhpIni.ps1`** (ten sam co Boost MCP): jednorazowo tworzy `php.ini` z WinGet i włącza `mbstring` i resztę. Ręcznie: `G:\gravity\tools\laravel-boost-mcp-runtime\Ensure-GravityPhpIni.ps1`. Aby pominąć: **`GRAVITY_SKIP_PHP_INI=1`**.
