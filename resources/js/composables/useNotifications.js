import Swal from 'sweetalert2';
import { SWAL_THEME } from '../utils/constants';


export function useNotifications() {
    const swalConfig = SWAL_THEME;

    
    const showConfirmDialog = async ({ title, text, icon = 'question', confirmButtonText = 'Confirm', cancelButtonText = 'Cancel' }) => {
        return await Swal.fire({
            ...swalConfig,
            title,
            text,
            icon,
            iconColor: icon === 'warning' ? '#ef4444' : '#fbbf24',
            showCancelButton: true,
            confirmButtonText,
            cancelButtonText,
            confirmButtonColor: icon === 'warning' ? '#ef4444' : '#fbbf24',
        });
    };

    
    const showSuccessToast = ({ title, text, timer = 1500 }) => {
        return Swal.fire({
            ...swalConfig,
            title,
            text,
            icon: 'success',
            iconColor: '#fbbf24',
            timer,
            showConfirmButton: false
        });
    };

    
    const showLoadingDialog = ({ title, text }) => {
        return Swal.fire({
            ...swalConfig,
            title,
            text,
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
    };

    
    const closeDialog = () => {
        Swal.close();
    };

    return {
        swalConfig,
        showConfirmDialog,
        showSuccessToast,
        showLoadingDialog,
        closeDialog
    };
}
