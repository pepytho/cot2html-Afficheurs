/**
 * Enhanced bracket visualization with interconnecting lines
 */

// Add hover effects to highlight connected matches
document.addEventListener('DOMContentLoaded', function() {
    const tableauCells = document.querySelectorAll('.myTableau td');
    
    tableauCells.forEach(cell => {
        // Add hover effect for fencer cells
        if (cell.classList.contains('Tableau_wto_lef') || cell.classList.contains('Tableau_wbo_lef')) {
            cell.addEventListener('mouseenter', function() {
                highlightConnectedCells(this);
            });
            
            cell.addEventListener('mouseleave', function() {
                clearHighlights();
            });
        }
    });
});

function highlightConnectedCells(cell) {
    // Add a subtle highlight to show the connection path
    const row = cell.parentNode;
    const table = cell.closest('table');
    const cellIndex = Array.from(row.children).indexOf(cell);
    
    // Highlight the current cell
    cell.classList.add('bracket-highlight');
    
    // Highlight connecting cells in the same row
    const nextScoreCell = row.children[cellIndex + 1];
    if (nextScoreCell) {
        nextScoreCell.classList.add('bracket-highlight');
    }
    
    // Find and highlight connecting lines to next round
    const nextRoundCells = findNextRoundCells(row, cellIndex, table);
    nextRoundCells.forEach(nextCell => {
        nextCell.classList.add('bracket-highlight');
    });
}

function findNextRoundCells(currentRow, cellIndex, table) {
    const connectedCells = [];
    const rows = Array.from(table.querySelectorAll('tr'));
    const currentRowIndex = rows.indexOf(currentRow);
    
    // Look for cells in the next round (every 3 columns represents a new round)
    const nextRoundColumnIndex = cellIndex + 3;
    
    // Find the corresponding cell in the next round
    if (nextRoundColumnIndex < currentRow.children.length) {
        const nextRoundCell = currentRow.children[nextRoundColumnIndex];
        if (nextRoundCell) {
            connectedCells.push(nextRoundCell);
        }
    }
    
    return connectedCells;
}

function clearHighlights() {
    const highlightedCells = document.querySelectorAll('.bracket-highlight');
    highlightedCells.forEach(cell => {
        cell.classList.remove('bracket-highlight');
    });
}

// Add CSS for clean highlight effect
const style = document.createElement('style');
style.textContent = `
    .bracket-highlight {
        border: 3px solid #FFD700 !important;
        transition: all 0.2s ease;
    }
    
    .myTableau td:hover.tbbr,
    .myTableau td:hover.tbr {
        opacity: 0.9;
        transition: all 0.2s ease;
    }
`;
document.head.appendChild(style);