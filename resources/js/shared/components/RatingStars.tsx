import React from 'react';

interface RatingStarsProps {
  rating: number; // e.g. 4.5
  max?: number; // default 5
  className?: string;
}

const RatingStars: React.FC<RatingStarsProps> = ({rating, max = 5, className = ''}) => {
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating - fullStars >= 0.5;
  const emptyStars = max - fullStars - (hasHalfStar ? 1 : 0);
  return (
    <span className={`flex items-center gap-0.5 ${className}`}>
      {Array.from({length: fullStars}).map((_, i) => (
        <i key={`full-${i}`} className="ki-duotone ki-star text-warning"/>
      ))}
      {hasHalfStar && <i className="ki-duotone ki-star text-gray-400"/>}
      {Array.from({length: emptyStars}).map((_, i) => (
        <i key={`empty-${i}`} className="ki-duotone ki-star text-gray-400"/>
      ))}
    </span>
  );
};

export default RatingStars;

