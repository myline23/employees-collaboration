# Employees — Longest Collaboration

PHP application that identifies the employee pair with the highest total number of shared project days.

Includes a command-line interface, a web interface for CSV uploads, and automated tests. Both interfaces use the same calculation services.

## Requirements

- PHP 8.2 or newer.
- File uploads enabled for the web interface.

No database, Composer packages, or external framework is required.

## Run the web application

From the project root:

```bash
php -S localhost:8000 -t public
```

Open http://localhost:8000, select a CSV file, and click **Analyze**.

The page displays the winning pair, their total shared project days, and a table containing:

- Employee ID #1
- Employee ID #2
- Project ID
- Days worked

Uploaded files are processed from PHP's temporary upload location and are not copied into the project. Upload limits follow the PHP configuration.

## Run from the command line

```bash
php index.php data/employees.csv
```

To process another file, provide its path:

```bash
php index.php "/path/to/another-file.csv"
```

Expected result for the included example, when run on or after October 8, 2026:

```text
Employees: 143, 218
Total days: 9
Project 10: 3 days
Project 20: 6 days
```

## CSV input

Each record must contain exactly four comma-separated fields:

```csv
EmpID,ProjectID,DateFrom,DateTo
143,10,2026-10-01,2026-10-05
218,10,2026-10-03,NULL
```

- The exact header shown above is optional and must be the first non-empty record.
- Empty lines are ignored.
- A UTF-8 BOM at the beginning of the file is supported.
- Surrounding field whitespace is removed.
- IDs must be non-negative integers within PHP's integer range, without leading zeros except for `0`.
- `DateTo` may contain `NULL`, case-insensitively.
- Empty dates and unrecognized dates are rejected.
- `DateFrom` must not be later than `DateTo`.

Dates containing commas must be quoted according to CSV rules:

```csv
143,10,"October 1, 2026","October 5, 2026"
```

## Supported date formats

The following PHP date formats are accepted:

| Formats | Examples |
|---|---|
| `Y-m-d` | `2026-10-03` |
| `d/m/Y`, `j/n/Y` | `03/10/2026`, `3/10/2026` |
| `d.m.Y`, `j.n.Y` | `03.10.2026`, `3.10.2026` |
| `d-m-Y`, `j-n-Y` | `03-10-2026`, `3-10-2026` |
| `Y/m/d`, `Y/n/j` | `2026/10/03`, `2026/10/3` |
| `d M Y`, `j M Y` | `03 Oct 2026`, `3 Oct 2026` |
| `d F Y`, `j F Y` | `03 October 2026`, `3 October 2026` |
| `M j, Y`, `F j, Y` | `Oct 3, 2026`, `October 3, 2026` |

Month names use English and the capitalization shown in the examples.

Numeric dates with the day first are interpreted as day/month/year. For example, `03/04/2026` means April 3, 2026.

Date parsing is strict: impossible dates such as `2026-02-30` are rejected. The application supports the listed formats rather than guessing arbitrary or ambiguous formats.

## Calculation rules and assumptions

1. Both the start and end dates are included. October 3–5 counts as three days.
2. `DateTo = NULL` means today in the `Europe/Sofia` timezone. Today is determined once per CSV read.
3. Employees must share both a project and overlapping dates to accumulate shared days.
4. Shared days are added across all common projects. If two projects run simultaneously, their shared days are counted separately.
5. An employee may have multiple separate participation periods within the same project.
6. Overlapping periods for the same employee and project, including duplicate records, are rejected rather than automatically merged.
7. Employee IDs within each pair are ordered numerically.
8. When totals are equal, the pair with the smaller first employee ID wins; if those IDs are equal, the smaller second ID wins.
9. If no two different employees have overlapping participation in a common project, no pair is returned.
10. Invalid CSV rows are reported with their record numbers. No result is calculated from a partially valid file.

## Algorithm

1. Read and validate the CSV records, converting date strings to date objects.
2. Group records by project ID.
3. Compare every pair of records within each project once.
4. For records belonging to the same employee, check that participation periods do not overlap.
5. For different employees, calculate the overlap:
   - Start: the later start date.
   - End: the earlier end date.
   - Shared days: zero if start is after end; otherwise the calendar-day difference plus one.
6. Accumulate shared days by employee pair and project.
7. Select the pair with the largest total and return its project breakdown.

For a project containing `n` records, pair comparison takes O(n²) time. Across all projects, the comparison cost is O(Σ nₚ²), where nₚ is the number of records in project p. Grouping reduces unnecessary comparisons between different projects.

## Structure

| Location | Responsibility |
|---|---|
| `src/Model/WorkRecord.php` | Immutable employee participation record |
| `src/Input/DateParser.php` | Strict date parsing |
| `src/Input/CsvReader.php` | CSV reading and row validation |
| `src/Service/OverlapCalculator.php` | Shared-day calculation for two records |
| `src/Service/TeamAnalyzer.php` | Project grouping, accumulation, and winner selection |
| `src/Controller/UploadController.php` | Uploaded-file validation and analysis coordination |
| `templates/employees.php` | Upload form and results table |
| `public/index.php` | Web entry point |
| `index.php` | Command-line entry point |
| `bootstrap.php` | Class loading and timezone configuration |
| `data/employees.csv` | Example input |
| `tests/run.php` | Automated checks |

## Tests

Run:

```bash
php tests/run.php
```

The suite contains 15 checks covering inclusive boundaries, absent overlaps, different projects, totals across projects, separate participation periods, tie-breaking, date formats, leap dates, NULL handling, and invalid CSV input.

Tests create and remove temporary CSV files. They do not modify the included example.

The test runner exits with code `0` when all checks pass and `1` when a check fails.