  <!-- Include jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <!-- Include SheetJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function () {
            $('#myTable').DataTable({
                "paging": true,
                "searching": false, // Custom search handled manually
                "order": [[0, "asc"]] // Default sorting by first column
            });
        });

        // Search by any key
        function searchTable(searchValue) {
            const table = document.getElementById('myTable');
            const rows = table.getElementsByTagName('tr');
            searchValue = searchValue.toLowerCase();

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                let found = false;
                
                for (let j = 0; j < row.cells.length - 1; j++) {
                    const cellText = row.cells[j].textContent.toLowerCase();
                    if (cellText.includes(searchValue)) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        }

        document.getElementById('searchInput').addEventListener('input', function(e) {
            searchTable(e.target.value);
        });

        // Export to Excel with borders
        function exportTable() {
            const table = document.getElementById("myTable");
            const rows = [];
            const tableRows = table.querySelectorAll("tr");

            tableRows.forEach((row) => {
                const rowData = [];
                row.querySelectorAll("td, th").forEach((cell, cellIndex) => {
                    if (cellIndex < row.cells.length - 1) { // Exclude last column
                        rowData.push(cell.innerText);
                    }
                });
                if (rowData.length > 0) rows.push(rowData);
            });

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(rows);

            // Add borders to Excel cells
            Object.keys(ws).forEach((key) => {
                if (!key.startsWith('!')) {
                    ws[key].s = {
                        border: {
                            top: { style: "thin", color: { rgb: "000000" } },
                            bottom: { style: "thin", color: { rgb: "000000" } },
                            left: { style: "thin", color: { rgb: "000000" } },
                            right: { style: "thin", color: { rgb: "000000" } }
                        }
                    };
                }
            });

            XLSX.utils.book_append_sheet(wb, ws, "Table Data");
            XLSX.writeFile(wb, "TableData.xlsx");
        }

        // Print table (excluding last column)
        function printTable() {
            const table = document.getElementById("myTable").cloneNode(true);
            const rows = table.querySelectorAll("tr");

            rows.forEach((row) => {
                const cells = row.querySelectorAll("td, th");
                if (cells.length > 0) {
                    cells[cells.length - 1].remove(); // Remove last column
                }
            });

            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write('<html><head><title>Print Table</title></head><body>');
            printWindow.document.write('<table border="1" style="border-collapse:collapse;width:100%;">' + table.innerHTML + '</table>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
