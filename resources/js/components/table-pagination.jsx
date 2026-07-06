import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { CardFooter } from '@/components/ui/card';

/**
 * @param {Object}   pagination              - Objeto de paginação do Laravel (via Inertia props)
 * @param {number}   pagination.current_page
 * @param {number}   pagination.last_page
 * @param {Function} onPageChange            - Callback chamado com o número da página destino
 */
export default function TablePagination({ pagination, onPageChange }) {
  if (!pagination?.current_page) return null;

  const isFirst = pagination.current_page === 1;
  const isLast = pagination.current_page === pagination.last_page;

  return (
    <CardFooter className="justify-between">
      <span className="text-muted-foreground">
        Página{' '}
        <span className="font-bold text-secondary">
          {pagination.current_page}
        </span>{' '}
        de{' '}
        <span className="font-bold text-secondary">{pagination.last_page}</span>
      </span>

      <Pagination>
        <PaginationContent>
          <PaginationItem>
            <PaginationPrevious
              onClick={() =>
                !isFirst && onPageChange(pagination.current_page - 1)
              }
              className={isFirst ? 'pointer-events-none opacity-50' : ''}
            />
          </PaginationItem>

          <PaginationItem>
            <PaginationNext
              onClick={() =>
                !isLast && onPageChange(pagination.current_page + 1)
              }
              className={isLast ? 'pointer-events-none opacity-50' : ''}
            />
          </PaginationItem>
        </PaginationContent>
      </Pagination>
    </CardFooter>
  );
}
