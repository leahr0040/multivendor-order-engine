import axios from 'axios';
import type { ValidationErrorResponse } from '@/types/api';

export function validationError(failure: unknown): ValidationErrorResponse {
    if (
        axios.isAxiosError<ValidationErrorResponse>(failure) &&
        failure.response?.data.errors
    ) {
        return failure.response.data;
    }

    return { message: 'Something went wrong. Please try again.', errors: {} };
}
