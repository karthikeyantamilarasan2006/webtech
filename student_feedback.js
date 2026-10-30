/**
 * Student Feedback Form Interactive Controller
 * Project: Student Feedback and Course Evaluation System
 */

document.addEventListener('DOMContentLoaded', function () {
    const ratingLabels = {
        1: 'Poor (1/5)',
        2: 'Fair (2/5)',
        3: 'Average (3/5)',
        4: 'Good (4/5)',
        5: 'Excellent (5/5)'
    };

    const criteriaNames = [
        'teaching_effectiveness',
        'subject_knowledge',
        'communication_skills',
        'course_content_quality',
        'classroom_interaction',
        'doubt_clarification',
        'overall_satisfaction'
    ];

    // Function to calculate and update real-time overall average score
    function updateLiveScore() {
        let total = 0;
        let count = 0;

        criteriaNames.forEach(function (name) {
            const checkedRadio = document.querySelector(`input[name="${name}"]:checked`);
            const badge = document.getElementById(`badge_${name}`);

            if (checkedRadio) {
                const val = parseInt(checkedRadio.value, 10);
                total += val;
                count++;
                if (badge) {
                    badge.textContent = ratingLabels[val] || `${val}/5`;
                    badge.className = 'badge rating-badge ' + (val >= 4 ? 'bg-success' : (val >= 3 ? 'bg-info text-dark' : 'bg-warning text-dark'));
                }
            } else if (badge) {
                badge.textContent = 'Not Rated';
                badge.className = 'badge rating-badge bg-secondary-subtle text-secondary';
            }
        });

        const liveScoreDisplay = document.getElementById('liveAverageScore');
        const liveProgressDisplay = document.getElementById('liveEvaluationProgress');

        if (liveScoreDisplay) {
            if (count > 0) {
                const avg = (total / count).toFixed(2);
                liveScoreDisplay.textContent = avg;
            } else {
                liveScoreDisplay.textContent = '0.00';
            }
        }

        if (liveProgressDisplay) {
            liveProgressDisplay.textContent = `${count} of ${criteriaNames.length} criteria rated`;
        }
    }

    // Attach change listener to all rating radio buttons
    criteriaNames.forEach(function (name) {
        const radios = document.querySelectorAll(`input[name="${name}"]`);
        radios.forEach(function (radio) {
            radio.addEventListener('change', updateLiveScore);
        });
    });

    // Initial score check on page load
    updateLiveScore();

    // -----------------------------------------------------------------
    // Dynamic Course -> Faculty Dropdown Cascade
    // -----------------------------------------------------------------
    const courseSelect = document.getElementById('course_id');
    const facultySelect = document.getElementById('faculty_id');
    const preselectedFacultyId = facultySelect ? facultySelect.getAttribute('data-selected') : null;

    if (courseSelect && facultySelect) {
        function loadFacultyForCourse(courseId, autoSelectId = null) {
            if (!courseId) {
                facultySelect.innerHTML = '<option value="">-- First Select Course --</option>';
                facultySelect.disabled = true;
                return;
            }

            facultySelect.disabled = true;
            facultySelect.innerHTML = '<option value="">Loading assigned faculty...</option>';

            fetch(`get_faculty_courses.php?action=get_faculty_by_course&course_id=${encodeURIComponent(courseId)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.faculty && data.faculty.length > 0) {
                        let optionsHtml = '<option value="">-- Select Faculty Member --</option>';
                        data.faculty.forEach(f => {
                            const isSelected = (autoSelectId && String(autoSelectId) === String(f.faculty_id)) ? 'selected' : '';
                            optionsHtml += `<option value="${f.faculty_id}" ${isSelected}>${f.full_name} (${f.designation})</option>`;
                        });
                        facultySelect.innerHTML = optionsHtml;
                        facultySelect.disabled = false;
                    } else {
                        facultySelect.innerHTML = '<option value="">No faculty assigned to this course yet</option>';
                        facultySelect.disabled = true;
                    }
                })
                .catch(err => {
                    console.error('Faculty fetch error:', err);
                    facultySelect.innerHTML = '<option value="">Error loading faculty</option>';
                    facultySelect.disabled = true;
                });
        }

        courseSelect.addEventListener('change', function () {
            loadFacultyForCourse(this.value);
        });

        // Trigger load if course is already selected (e.g., passed in GET param)
        if (courseSelect.value) {
            loadFacultyForCourse(courseSelect.value, preselectedFacultyId);
        }
    }

    // -----------------------------------------------------------------
    // Comprehensive Submission Validation
    // -----------------------------------------------------------------
    const feedbackForm = document.getElementById('feedbackEvaluationForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function (e) {
            let missingCriteria = [];

            criteriaNames.forEach(function (name) {
                const checked = document.querySelector(`input[name="${name}"]:checked`);
                if (!checked) {
                    missingCriteria.push(name.replace(/_/g, ' '));
                }
            });

            if (missingCriteria.length > 0) {
                e.preventDefault();
                alert(`Please provide a star rating for all criteria.\n\nMissing ratings for:\n- ${missingCriteria.join('\n- ')}`);
                
                // Focus first missing rating group
                const firstMissingGroup = document.querySelector(`input[name="${criteriaNames.find(n => missingCriteria.includes(n.replace(/_/g, ' ')))}"]`);
                if (firstMissingGroup) {
                    firstMissingGroup.closest('.card').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            const isAnonymous = document.getElementById('is_anonymous')?.checked;
            const anonMsg = isAnonymous 
                ? 'Your feedback will be submitted ANONYMOUSLY. Your identity will NOT be linked.' 
                : 'Your feedback will be recorded with your student verification.';

            if (!confirm(`Are you ready to submit your course evaluation?\n\n${anonMsg}`)) {
                e.preventDefault();
                return false;
            }
        });
    }
});

