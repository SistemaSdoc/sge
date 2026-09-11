import React from 'react';

const PaginationWrapper = ({ links, onPageChange }) => {
  if (!links || links.length === 0) return null;

  return (
    <nav className="d-flex justify-content-center">
      <ul className="pagination">
        {links.map((link, index) => {
          const isActive = link.active || false;
          const isDisabled = !link.url;

          return (
            <li
              key={index}
              className={`page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`}
            >
              <button
                className="page-link"
                onClick={() => {
                  if (!isDisabled && onPageChange) {
                    const page = new URL(link.url).searchParams.get('page') || 1;
                    onPageChange(page);
                  }
                }}
                disabled={isDisabled}
                dangerouslySetInnerHTML={{ __html: link.label }}
              />
            </li>
          );
        })}
      </ul>
    </nav>
  );
};

export default PaginationWrapper;