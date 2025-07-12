import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function valueUpdater<T>(
    updaterOrValue: T | ((old: T) => T),
    previousValue: { value: T }
  ) {
    previousValue.value =
      typeof updaterOrValue === 'function'
        ? (updaterOrValue as (old: T) => T)(previousValue.value)
        : updaterOrValue;
  }
