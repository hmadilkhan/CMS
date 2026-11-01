{{-- Masks every input[data-phone-mask] as a US number: (123) 123-9090 --}}
<script>
    (function() {
        function format(raw) {
            var digits = String(raw || '').replace(/\D/g, '');
            if (digits.length > 10 && digits.charAt(0) === '1') {
                digits = digits.substring(1);
            }
            digits = digits.substring(0, 10);

            if (digits.length === 0) return '';
            if (digits.length < 4) return '(' + digits;
            if (digits.length < 7) return '(' + digits.substring(0, 3) + ') ' + digits.substring(3);
            return '(' + digits.substring(0, 3) + ') ' + digits.substring(3, 6) + '-' + digits.substring(6);
        }

        function bind(input) {
            input.setAttribute('maxlength', '20');
            input.setAttribute('inputmode', 'tel');
            input.setAttribute('autocomplete', 'tel-national');
            input.setAttribute('placeholder', '(123) 123-9090');

            // Never emits a trailing separator, so backspace always removes a digit.
            input.addEventListener('input', function() {
                input.value = format(input.value);
            });

            // A stored value that is not a full US number is shown exactly as saved.
            var loaded = input.value.replace(/\D/g, '');
            if (loaded.length > 10 && loaded.charAt(0) === '1') {
                loaded = loaded.substring(1);
            }
            if (loaded.length === 10) {
                input.value = format(input.value);
            }
        }

        document.querySelectorAll('input[data-phone-mask]').forEach(bind);
    })();
</script>
