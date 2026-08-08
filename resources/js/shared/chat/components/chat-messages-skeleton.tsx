import React from 'react';

/**
 * Pulsing placeholder bubbles while chat history loads.
 * Visual language matches TopUp Deferred CardSkeleton (bg-light + animate-pulse).
 */
const ChatMessagesSkeleton = () => {
  const rows: Array<{ align: 'start' | 'end'; width: string }> = [
    { align: 'start', width: '55%' },
    { align: 'end', width: '45%' },
    { align: 'start', width: '65%' },
    { align: 'end', width: '40%' },
    { align: 'start', width: '50%' },
  ];

  return (
    <div
      className="d-flex flex-column gap-5 py-4 pe-2"
      aria-busy="true"
      aria-live="polite"
    >
      {rows.map((row, index) => (
        <div
          key={index}
          className={`d-flex justify-content-${row.align}`}
        >
          <div
            className="rounded bg-light animate-pulse"
            style={{
              width: row.width,
              maxWidth: 280,
              height: index % 3 === 0 ? 64 : 44,
            }}
          />
        </div>
      ))}
    </div>
  );
};

export default ChatMessagesSkeleton;
