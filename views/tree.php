<?php
if (!defined('APP_STARTED')) {
    die('Forbidden!');
}

function tree(array $array, string $parent, array $parts = [], int|false $step = 0): string
{
    if (!count($array)) {
        return '';
    }

    $tid = ($step == 0) ? 'id="tree"' : '';
    $t = '<ul class="unstyled" '.$tid.'>';

    foreach ($array as $key => $item) {
        if (is_array($item)) {
            $open = $step !== false && isset($parts[$step]) && (string) $key === $parts[$step];
            $folderUrl = $parent . '/' . rawurlencode((string) $key);
            $hasChildren = count($item) > 0;

            $t .= '<li class="directory'. ($open ? ' open' : '') .'">';
                $t .= '<div class="directory-row">';
                    $t .= '<a href="'. e($folderUrl) .'"><i class="far fa-folder'. ($open ? '-open' : '') .'"></i>&nbsp; ' . e((string) $key) . '</a>';
                    if ($hasChildren) {
                        $t .= '<button type="button" class="directory-toggle" data-role="directory-toggle" aria-label="'. ($open ? 'Collapse' : 'Expand') .' ' . e((string) $key) .'" aria-expanded="'. ($open ? 'true' : 'false') .'">';
                        $t .= '<i class="fas fa-chevron-right" aria-hidden="true"></i></button>';
                    }
                $t .= '</div>';
                $t .= tree($item, $parent . '/' . rawurlencode((string) $key), $parts, $open ? $step + 1 : false);
            $t .=  '</li>';
        } else {
            $selected = isset($parts[$step]) && (string) $item === $parts[$step];
            $fileName = (string) $item;
            $t .= '<li class="file'. ($selected ? ' active' : '') .'"><a href="'. e($parent . '/' . rawurlencode($fileName)) .'">'. e($fileName) .'</a></li>';
        }
    }

    $t .= '</ul>';

    return $t;
}
?>

<div id="tree-filter" class="input-group">
  <input type="text" id="tree-filter-query" class="form-control" placeholder="Search file &amp; directory names." aria-label="Search" aria-describedby="search-addon">
  <div class="input-group-append">
    <button type="button" id="tree-filter-clear-query" class="btn  btn-outline-secondary" title="Clear current search..." disabled>
      <i class="fas fa-times"></i>
    </button>
  </div>
</div>

<ul class="unstyled" id="tree-filter-results"></ul>

<?php echo tree($this->_getTree(), BASE_URL, $parts ?? []); ?>

<script>
    // Case-insensitive alternative to :contains():
    // All credit to Mina Gabriel:
    // http://stackoverflow.com/a/15033857/443373
    $.expr[':'].containsIgnoreCase = (node, index, match) =>
        jQuery(node).text().toUpperCase().includes(match[3].toUpperCase());

    //enable / disable buttons
    jQuery.fn.extend({
      disable(state) {
        return this.each(function() {
            this.disabled = state;
        });
      }
    });

    $(function() {
        const tree = $('#tree');
        const resultsTree = $('#tree-filter-results');
        const filterInput = $('#tree-filter-query');
        const clearFilterInput = $('#tree-filter-clear-query');

        // Auto-focus the search field:
        filterInput.focus();

        // Cancels a filtering action and puts everything back
        // in its place:
        const cancelFilterAction = () => {
            filterInput.val('').removeClass('active');
            resultsTree.empty();
            tree.show();
            clearFilterInput.disable(true);
        };

        // Clear the filter input when the X to its right is clicked:
        clearFilterInput.on('click', cancelFilterAction);


        // Same thing if the user presses ESC and the filter is active:
        $(document).on('keyup', (event) => {
            if (event.key === 'Escape' && filterInput.hasClass('active')) {
                cancelFilterAction();
            }
        });

        // Perform live searches as the user types:
        // @todo: check support for 'input' event across more browsers?
        filterInput.on('input', function() {
            const value = filterInput.val() ?? '';
            let query = value.trim();
            const isActive = value !== '';

            // Add a visual cue to show that the filter function is active:
            filterInput.toggleClass('active', isActive);

            clearFilterInput.disable(false);

            // If we have no query, cleanup and bail out:
            if(!isActive) {
                cancelFilterAction();
                return;
            }

            // Hide the actual tree before displaying the fake results tree:
            if(tree.is(':visible')) {
                tree.hide();
            }

            // Sanitize the search query so it won't so easily break
            // the :contains operator:
            query = query
                        .replace(/\(/g, '\\(')
                        .replace(/\)/g, '\\)')
                    ;

            // Get all nodes containing the search query (searches for all a, and returns
            // their parent nodes <li>).
            resultsTree.html(tree.find('a:containsIgnoreCase(' + query + ')').closest('li').clone());
        });

        // Handle directory-tree expansion:
        $(document).on('click', '#sidebar button[data-role="directory-toggle"]', function (event) {
            event.preventDefault();
            const button = $(this);
            const folder = button.closest('li.directory');
            const icon = folder.children('.directory-row').find('.far').first();
            const subtree = folder.children('ul').first();
            const open = !folder.hasClass('open');

            folder.toggleClass('open', open);
            button.attr('aria-expanded', open ? 'true' : 'false');
            button.attr('aria-label', (open ? 'Collapse ' : 'Expand ') + folder.children('.directory-row').children('a').text().trim());
            icon.toggleClass('fa-folder', !open).toggleClass('fa-folder-open', open);

            if (open) {
                subtree.slideDown({ duration: 100 });
            } else {
                subtree.slideUp({ duration: 100 });
            }
        });
    });
</script>
